<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmAgendaLetter;
use App\Models\Hcm\HcmApplicantInterview;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmExternalLetter;
use App\Models\Hcm\HcmInternalDocument;
use App\Models\Hcm\HcmJobApplicant;
use App\Models\Hcm\HcmJobPosting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HcmRecruitmentAndDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds for recruitment, agenda letters, external letters, and internal SOPs.
     */
    public function run(): void
    {
        $hcmUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();

        // 1. Lowongan Kerja (Job Postings)
        $postings = [
            [
                'job_code' => 'LKR-2026-001',
                'title' => 'Operator Jahit Garment & Sportswear',
                'slug' => 'operator-jahit-garment-sportswear',
                'department' => 'Produksi',
                'position' => 'Jahit',
                'job_type' => 'Penuh Waktu (Full Time)',
                'location' => 'Pabrik Lamongan',
                'quota' => 5,
                'min_education' => 'SMA / SMK / Sederajat',
                'min_experience_years' => 1,
                'salary_range' => 'Rp 2.400.000 - Rp 3.200.000',
                'salary_range_min' => 2400000,
                'salary_range_max' => 3200000,
                'description' => 'Bertanggung jawab melakukan proses jahit produk jersey dan sportswear sesuai standar spesifikasi SPK.',
                'requirements' => "1. Mampu mengoperasikan mesin jahit high-speed (jarum 1, obras, overdeck).\n2. Memiliki pengalaman minimal 1 tahun di konveksi/garment.\n3. Teliti, rapi, dan mampu bekerja sesuai target harian.",
                'benefits' => 'Gaji pokok, Uang makan bulanan, Uang lembur mingguan, BPJS Kesehatan & Ketenagakerjaan.',
                'deadline' => '2026-10-31',
                'is_active' => true,
                'status' => 'Aktif',
                'views_count' => 142,
            ],
            [
                'job_code' => 'LKR-2026-002',
                'title' => 'Graphic Designer Sportswear Apparel',
                'slug' => 'graphic-designer-sportswear-apparel',
                'department' => 'Marketing',
                'position' => 'Designer',
                'job_type' => 'Penuh Waktu (Full Time)',
                'location' => 'Kantor Pusat Lamongan',
                'quota' => 2,
                'min_education' => 'Diploma 3 (D3)',
                'min_experience_years' => 1,
                'salary_range' => 'Rp 3.000.000 - Rp 4.500.000',
                'salary_range_min' => 3000000,
                'salary_range_max' => 4500000,
                'description' => 'Membuat desain mock up jersey custom, layout pola printing sublime, serta materi visual promosi brand.',
                'requirements' => "1. Menguasai Adobe Illustrator, Photoshop, dan CorelDRAW.\n2. Memahami pola garment jersey sublime adalah nilai plus.\n3. Memiliki portofolio desain jersey/sportswear yang kuat.",
                'benefits' => 'Gaji pokok kompetitif, Uang makan, Bonus KPI bulanan, BPJS lengkap, Lingkungan kerja kreatif.',
                'deadline' => '2026-10-20',
                'is_active' => true,
                'status' => 'Aktif',
                'views_count' => 310,
            ],
            [
                'job_code' => 'LKR-2026-003',
                'title' => 'Quality Control (QC) & Finishing Garment',
                'slug' => 'quality-control-finishing-garment',
                'department' => 'Produksi',
                'position' => 'Quality Control',
                'job_type' => 'Penuh Waktu (Full Time)',
                'location' => 'Pabrik Lamongan',
                'quota' => 3,
                'min_education' => 'SMA / SMK / Sederajat',
                'min_experience_years' => 0,
                'salary_range' => 'Rp 2.400.000 - Rp 2.800.000',
                'salary_range_min' => 2400000,
                'salary_range_max' => 2800000,
                'description' => 'Melakukan inspeksi mutu jahitan, kerapian benang, akurasi nameset dan logo, serta packing akhir sebelum kirim.',
                'requirements' => "1. Detail oriented dan teliti terhadap cacat jahitan/kain.\n2. Cekatan, disiplin, dan bertanggung jawab.\n3. Fresh graduate dipersilakan melamar.",
                'benefits' => 'Gaji pokok, Uang makan, Fasilitas lembur, Pelatihan QC bersertifikat internal.',
                'deadline' => '2026-10-15',
                'is_active' => true,
                'status' => 'Aktif',
                'views_count' => 95,
            ],
            [
                'job_code' => 'LKR-2026-004',
                'title' => 'Operator Mesin Press Sublime',
                'slug' => 'operator-mesin-press-sublime',
                'department' => 'Produksi',
                'position' => 'Press Sublime',
                'job_type' => 'Penuh Waktu (Full Time)',
                'location' => 'Pabrik Lamongan',
                'quota' => 2,
                'min_education' => 'SMA / SMK / Sederajat',
                'min_experience_years' => 1,
                'salary_range' => 'Rp 2.500.000 - Rp 3.000.000',
                'salary_range_min' => 2500000,
                'salary_range_max' => 3000000,
                'description' => 'Mengoperasikan mesin transfer press rotary dan flatbed untuk transfer motif sublimasi ke bahan kain.',
                'requirements' => "1. Paham suhu, tekanan, dan waktu transfer kain poliester/dryfit.\n2. Tahan terhadap area kerja hangat mesin pemanas.\n3. Fisik prima dan stamina kuat.",
                'benefits' => 'Gaji pokok, Uang makan, Ekstra suplemen susu bulanan, Lembur.',
                'deadline' => '2026-09-25',
                'is_active' => false,
                'status' => 'Ditutup',
                'views_count' => 180,
            ],
        ];

        $createdPostings = [];
        foreach ($postings as $p) {
            $createdPostings[] = HcmJobPosting::updateOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, ['created_by' => $hcmUser?->id])
            );
        }

        // 2. Data Pelamar Kerja (Job Applicants)
        $applicants = [
            [
                'job_slug' => 'graphic-designer-sportswear-apparel',
                'applicant_code' => 'APL-2026-0001',
                'name' => 'Bayu Wicaksono',
                'nickname' => 'Bayu',
                'gender' => 'Laki-Laki',
                'birth_place' => 'Surabaya',
                'birth_date' => '2001-07-14',
                'phone_number' => '081234111222',
                'email' => 'bayu.wicak@gmail.com',
                'education' => 'Strata 1 (S1)',
                'major' => 'Desain Komunikasi Visual',
                'address' => 'Gubeng Kertajaya, Surabaya',
                'expected_salary' => 3800000,
                'status' => 'INTERVIEW',
                'interview_date' => '2026-10-02 10:00:00',
                'interview_location' => 'Ruang Meeting HCM - Lt. 2',
                'interviewer_notes' => 'Portofolio mockup jersey futsal sangat menarik. Memiliki pemahaman teknik separasi sublim.',
                'interview' => [
                    'interview_round' => 'Interview User & Tes Praktek',
                    'interviewer_name' => 'Bintang Ramadhan (Lead Designer)',
                    'interview_date' => '2026-10-02',
                    'interview_result' => 'Direkomendasikan Lolos',
                    'age' => 25,
                    'marital_status' => 'Belum Menikah',
                    'education' => 'S1 DKV',
                    'last_experience' => 'Desainer Grafis di Studio Sablon Surabaya (2 Tahun)',
                    'daily_activity' => 'Freelance design jersey komunitas dan apparel esport',
                    'core_skills' => 'Adobe Illustrator, Photoshop, Mockup 3D CLO, Pola Sublime',
                    'salary_expectation' => 3800000,
                    'offering_status' => 'Dalam Negosiasi',
                    'interview_decision' => 'LOLOS',
                    'offering_notes' => 'Bakat visual sangat cocok dengan branding Allegiant Apparel.',
                ],
            ],
            [
                'job_slug' => 'operator-jahit-garment-sportswear',
                'applicant_code' => 'APL-2026-0002',
                'name' => 'Kurniawati',
                'nickname' => 'Kurnia',
                'gender' => 'Perempuan',
                'birth_place' => 'Lamongan',
                'birth_date' => '1998-03-21',
                'phone_number' => '081234111333',
                'email' => 'kurniawati98@gmail.com',
                'education' => 'SMA / SMK / Sederajat',
                'major' => 'Tata Busana',
                'address' => 'Ds. Plosowahyu Kec. Lamongan',
                'expected_salary' => 2700000,
                'status' => 'SCREENING',
                'interviewer_notes' => 'Pengalaman 3 tahun di pabrik garmen ekspor Semarang. Berkas lengkap.',
            ],
            [
                'job_slug' => 'quality-control-finishing-garment',
                'applicant_code' => 'APL-2026-0003',
                'name' => 'Reza Pahlevi',
                'nickname' => 'Reza',
                'gender' => 'Laki-Laki',
                'birth_place' => 'Lamongan',
                'birth_date' => '2004-12-01',
                'phone_number' => '081234111444',
                'email' => 'reza.pahlevi@gmail.com',
                'education' => 'SMA / SMK / Sederajat',
                'major' => 'IPA',
                'address' => 'Kec. Turi, Kab. Lamongan',
                'expected_salary' => 2400000,
                'status' => 'SUBMITTED',
            ],
            [
                'job_slug' => 'operator-jahit-garment-sportswear',
                'applicant_code' => 'APL-2026-0004',
                'name' => 'Wahyu Hidayat',
                'nickname' => 'Wahyu',
                'gender' => 'Laki-Laki',
                'birth_place' => 'Bojonegoro',
                'birth_date' => '1995-09-09',
                'phone_number' => '081234111555',
                'email' => 'wahyu.h@gmail.com',
                'education' => 'SMP / Sederajat',
                'address' => 'Kec. Baureno, Kab. Bojonegoro',
                'expected_salary' => 2500000,
                'status' => 'REJECTED',
                'rejection_reason' => 'Tidak memenuhi kualifikasi pengalaman jahit jersey olahraga elastis.',
            ],
            [
                'job_slug' => 'operator-jahit-garment-sportswear',
                'applicant_code' => 'APL-2026-0005',
                'name' => 'Danang Prasetyo', // Pelamar yang berhasil dikonversi menjadi Karyawan!
                'nickname' => 'Danang',
                'gender' => 'Laki-Laki',
                'birth_place' => 'Lamongan',
                'birth_date' => '2001-11-09',
                'phone_number' => '085234567892',
                'email' => 'danang.prasetyo@gmail.com',
                'education' => 'SMA / SMK / Sederajat',
                'address' => 'Lamongan, Jawa Timur',
                'expected_salary' => 2400000,
                'status' => 'ACCEPTED',
                'converted_at' => Carbon::create(2026, 1, 1),
            ],
        ];

        $convertedEmp = HcmEmployee::where('employee_code', 'EMP-2026-003')->first();

        foreach ($applicants as $appData) {
            $posting = HcmJobPosting::where('slug', $appData['job_slug'])->first();
            if (!$posting) {
                continue;
            }

            $interviewData = $appData['interview'] ?? null;
            unset($appData['job_slug'], $appData['interview']);

            $appData['job_posting_id'] = $posting->id;
            if ($appData['name'] === 'Danang Prasetyo' && $convertedEmp) {
                $appData['converted_employee_id'] = $convertedEmp->id;
            }

            $applicant = HcmJobApplicant::updateOrCreate(
                ['applicant_code' => $appData['applicant_code']],
                $appData
            );

            if ($interviewData) {
                $interviewData['applicant_id'] = $applicant->id;
                $interviewData['created_by'] = $hcmUser?->id;
                HcmApplicantInterview::updateOrCreate(
                    ['applicant_id' => $applicant->id, 'interview_round' => $interviewData['interview_round']],
                    $interviewData
                );
            }
        }

        // 3. Agenda Surat Masuk & Keluar (HcmAgendaLetter)
        $agendaLetters = [
            [
                'letter_type' => 'SURAT_MASUK',
                'agenda_number' => 'AGD-2026-0001',
                'letter_number' => '421.5/128/SMK2-LMG/VII/2026',
                'letter_date' => '2026-07-15',
                'received_or_sent_date' => '2026-07-16',
                'sender' => 'Kepala Sekolah SMK Negeri 2 Lamongan',
                'recipient' => 'Pimpinan CV Bawang Merah (NIS Group)',
                'subject' => 'Permohonan Praktik Kerja Lapangan (PKL) Siswa Kompetensi Keahlian Tata Busana Periode Juli - Oktober 2026',
                'category' => 'Kerjasama Pendidikan / Magang',
                'file_url' => '/storage/hcm/letters/surat_permohonan_pkl_smk2.pdf',
                'physical_location' => 'Bantex Dokumen HCM - Map Magang 2026',
                'notes' => 'Disetujui 2 orang siswi: Siti Nurhaliza & Anisa Rahmawati.',
            ],
            [
                'letter_type' => 'SURAT_KELUAR',
                'agenda_number' => 'AGD-2026-0002',
                'letter_number' => '045/NIS-HCM/EXT/VII/2026',
                'letter_date' => '2026-07-20',
                'received_or_sent_date' => '2026-07-20',
                'sender' => 'Human Capital Management NIS Group',
                'recipient' => 'Kepala Sekolah SMK Negeri 2 Lamongan',
                'subject' => 'Konfirmasi Penerimaan Siswa Praktik Kerja Lapangan (PKL)',
                'category' => 'Kerjasama Pendidikan / Magang',
                'file_url' => '/storage/hcm/letters/surat_balasan_pkl_smk2.pdf',
                'physical_location' => 'Bantex Dokumen HCM - Map Surat Keluar',
                'notes' => 'Tembusan disampaikan kepada pembimbing sekolah Ibu Ningsih.',
            ],
            [
                'letter_type' => 'SK_DIREKSI',
                'agenda_number' => 'AGD-2026-0003',
                'letter_number' => 'SK-DIR/NIS/012/VIII/2026',
                'letter_date' => '2026-08-01',
                'received_or_sent_date' => '2026-08-01',
                'sender' => 'Direktur Utama NIS Group',
                'recipient' => 'Seluruh Karyawan dan Staf Produksi',
                'subject' => 'Surat Keputusan Direksi tentang Penyesuaian Tarif Lembur dan Kebijakan Uang Makan Karyawan 2026',
                'category' => 'Kebijakan & SK Direksi',
                'file_url' => '/storage/hcm/letters/sk_direksi_lembur_uang_makan.pdf',
                'physical_location' => 'Bantex Master SK & Regulasi Perusahaan',
                'notes' => 'Berlaku efektif sejak 1 Agustus 2026.',
            ],
            [
                'letter_type' => 'INTERNAL_MEMO',
                'agenda_number' => 'AGD-2026-0004',
                'letter_number' => 'MEMO/HCM-NIS/088/IX/2026',
                'letter_date' => '2026-09-10',
                'received_or_sent_date' => '2026-09-10',
                'sender' => 'Admin HCM NIS Group',
                'recipient' => 'Seluruh Kepala Divisi & Supervisor',
                'subject' => 'Sosialisasi Tata Tertib Presensi Digital dan Kepatuhan Jam Masuk Kerja',
                'category' => 'Internal Memo',
                'file_url' => '/storage/hcm/letters/memo_sosialisasi_presensi.pdf',
                'physical_location' => 'Bantex Internal Memo',
                'notes' => 'Ditempelkan pada papan pengumuman pabrik dan broadcast grup koordinasi WA.',
            ],
        ];

        foreach ($agendaLetters as $lData) {
            $lData['created_by'] = $hcmUser?->id;
            HcmAgendaLetter::updateOrCreate(
                ['agenda_number' => $lData['agenda_number']],
                $lData
            );
        }

        // 4. Korespondensi Surat Eksternal (HcmExternalLetter)
        $externalLetters = [
            [
                'registration_no' => 'DOC-EXT-2026-0001',
                'letter_date' => '2026-08-14',
                'direction' => 'Surat Masuk',
                'external_letter_no' => 'B/145/082026/BPJS-TK/BOJ',
                'sender' => 'BPJS Ketenagakerjaan Kantor Cabang Bojonegoro / Lamongan',
                'recipient' => 'Pimpinan CV Bawang Merah',
                'subject' => 'Pemutakhiran Data Validasi NIK dan Kepesertaan JKK & JKM Karyawan Aktif 2026',
                'file_scan_url' => '/storage/hcm/letters/bpjs_pemutakhiran.pdf',
                'notes' => 'Telah ditindaklanjuti dan disinkronkan dengan data NIK KTP karyawan.',
                'created_by' => $hcmUser?->id,
            ],
            [
                'registration_no' => 'DOC-EXT-2026-0002',
                'letter_date' => '2026-08-25',
                'direction' => 'Surat Keluar',
                'external_letter_no' => '052/NIS-CORP/DISNAKER/VIII/2026',
                'sender' => 'Direksi CV Bawang Merah (NIS Group)',
                'recipient' => 'Dinas Tenaga Kerja Kab. Lamongan',
                'subject' => 'Laporan Wajib Ketenagakerjaan Perusahaan (WLKP) Periode Semester 1 Tahun 2026',
                'file_scan_url' => '/storage/hcm/letters/laporan_wlkp_2026.pdf',
                'notes' => 'Tanda terima fisik tersimpan di arsip legalitas perusahaan.',
                'created_by' => $hcmUser?->id,
            ],
            [
                'registration_no' => 'DOC-EXT-2026-0003',
                'letter_date' => '2026-09-02',
                'direction' => 'Surat Masuk',
                'external_letter_no' => 'BRI/LMG/KPO/2026/09/441',
                'sender' => 'PT Bank Rakyat Indonesia (Persero) Tbk Kantor Cabang Lamongan',
                'recipient' => 'Bagian Keuangan & HCM NIS Group',
                'subject' => 'Fasilitas Layanan Payroll Massal dan Pembukaan Rekening Kolektif Karyawan',
                'file_scan_url' => '/storage/hcm/letters/bri_payroll_program.pdf',
                'notes' => 'Koordinasi pembukaan rekening untuk batch pekerja kontrak baru.',
                'created_by' => $hcmUser?->id,
            ],
        ];

        foreach ($externalLetters as $ext) {
            HcmExternalLetter::updateOrCreate(
                ['registration_no' => $ext['registration_no']],
                $ext
            );
        }

        // 5. Dokumen Internal & SOP Perusahaan (HcmInternalDocument)
        $internalDocs = [
            [
                'document_code' => 'SOP-PRD-001',
                'title' => 'SOP Pengoperasian Mesin Printing Sublime dan Standar Warna Kain',
                'category' => 'Standard Operating Procedure (SOP)',
                'revision_number' => 'Rev. 02',
                'effective_date' => '2026-01-01',
                'status' => 'Aktif',
                'file_url' => '/storage/hcm/documents/sop_sublime_printing.pdf',
                'description' => 'Panduan baku kalibrasi profil warna CMYK, penanganan kertas transfer, dan pembersihan nozzle print head harian.',
            ],
            [
                'document_code' => 'SOP-QC-002',
                'title' => 'SOP Quality Control Garment, Pemeriksaan Pola, & Toleransi Ukuran Size Chart',
                'category' => 'Standard Operating Procedure (SOP)',
                'revision_number' => 'Rev. 01',
                'effective_date' => '2026-03-01',
                'status' => 'Aktif',
                'file_url' => '/storage/hcm/documents/sop_quality_control.pdf',
                'description' => 'Standar baku toleransi ukuran maksimal 1-2 cm, cek list kerapian jahitan stik balik, serta kebersihan noda sisa tinta.',
            ],
            [
                'document_code' => 'PP-NIS-2026',
                'title' => 'Peraturan Perusahaan dan Kode Etik Kedisiplinan Kerja NIS Group 2026 - 2028',
                'category' => 'Dokumen Kebijakan & SK',
                'revision_number' => 'Rev. 03',
                'effective_date' => '2026-01-01',
                'status' => 'Aktif',
                'file_url' => '/storage/hcm/documents/peraturan_perusahaan_nis.pdf',
                'description' => 'Buku pedoman hak dan kewajiban pekerja, jam operasional kerja, mekanisme cuti, tata tertib keselamatan kerja, dan sanksi kedisiplinan.',
            ],
            [
                'document_code' => 'FRM-HCM-001',
                'title' => 'Formulir Standar Pengajuan Cuti, Izin, dan Sakit Karyawan',
                'category' => 'Dokumen Pengajuan',
                'revision_number' => 'Rev. 00',
                'effective_date' => '2026-02-01',
                'status' => 'Aktif',
                'file_url' => '/storage/hcm/documents/form_cuti_izin.pdf',
                'description' => 'Formulir resmi yang wajib diisi dan ditandatangani atasan langsung saat mengajukan dispensasi ketidakhadiran.',
            ],
            [
                'document_code' => 'K3-PRD-001',
                'title' => 'Petunjuk Keselamatan Kerja (K3) Area Bengkel Potong dan Area Panas Mesin Heat Press',
                'category' => 'Standard Operating Procedure (SOP)',
                'revision_number' => 'Rev. 01',
                'effective_date' => '2026-04-15',
                'status' => 'Aktif',
                'file_url' => '/storage/hcm/documents/pedoman_k3_pabrik.pdf',
                'description' => 'Kewajiban penggunaan sarung tangan anti-potong bagi operator mesin potong vertikal dan penggunaan alas kaki pelindung.',
            ],
        ];

        foreach ($internalDocs as $doc) {
            $doc['created_by'] = $hcmUser?->id;
            HcmInternalDocument::updateOrCreate(
                ['document_code' => $doc['document_code']],
                $doc
            );
        }
    }
}
