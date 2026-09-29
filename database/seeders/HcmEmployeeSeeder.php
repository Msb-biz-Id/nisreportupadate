<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmCompensation;
use App\Models\Hcm\HcmCompensationHistory;
use App\Models\Hcm\HcmContract;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmIntern;
use Illuminate\Database\Seeder;

class HcmEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds with factual data from Blueprint Excel.
     */
    public function run(): void
    {
        // 1. Bambang Sadewo (Managerial - Tetap)
        $bambang = HcmEmployee::updateOrCreate(
            ['nik_ktp' => '3524012010010001'],
            [
                'employee_code' => 'EMP-2026-001',
                'name' => 'Bambang Sadewo',
                'nickname' => 'Bambang',
                'department' => 'Produksi',
                'position' => 'PIC Produksi',
                'job_level' => 'Managerial',
                'employment_status' => 'Karyawan Tetap',
                'legal_entity' => 'CV Bawang Merah',
                'phone_number' => '085234567890',
                'gender' => 'Laki Laki',
                'religion' => 'Islam',
                'education' => 'SMA Sederajat',
                'marital_status' => 'Belum Menikah',
                'birth_place' => 'Lamongan',
                'birth_date' => '2001-10-20',
                'bpjs_kesehatan_no' => '0001234567891',
                'bpjs_ketenagakerjaan_no' => '0009876543211',
                'shirt_size' => 'XL',
                'address' => 'RT.03 RW. 01 Ds. Kwangya Kec. Lamongan Kab. Lamongan Jawa Timur',
                'bank_account_no' => '0011-01-098765-50-1',
                'bank_name' => 'Bank BRI',
                'email' => 'bambang.unyuk@gmail.com',
                'join_date' => '2024-08-01',
                'is_active' => true,
            ]
        );

        HcmContract::updateOrCreate(
            ['contract_number' => '001/OWR/PKWT/X/2026'],
            [
                'employee_id' => $bambang->id,
                'contract_sequence' => 3,
                'employment_status' => 'Karyawan Tetap',
                'position' => 'PIC Produksi',
                'legal_entity' => 'CV Bawang Merah',
                'duration_text' => 'Tetap',
                'trainee_start_month' => 'Agustus',
                'trainee_end_month' => 'Oktober',
                'contract_month' => 'September',
                'start_year' => 2026,
                'start_date' => '2026-08-01',
                'end_date' => null,
                'review_status' => 'Aktif',
            ]
        );

        $compBambang = HcmCompensation::updateOrCreate(
            ['employee_id' => $bambang->id],
            [
                'employment_status' => 'Karyawan Tetap',
                'legal_entity' => 'CV Bawang Merah',
                'contract_number' => '001/OWR/PKWT/X/2026',
                'duration_text' => 'Tetap',
                'trainee_duration_months' => 24,
                'evaluation_cycle_months' => 6,
                'initial_salary' => 3500000,
                'current_salary' => 4200000,
                'salary_increment_count' => 2,
                'increment_1_amount' => 350000,
                'increment_2_amount' => 350000,
                'salary_status' => 'Telah Berlaku',
            ]
        );

        HcmCompensationHistory::firstOrCreate(
            [
                'compensation_id' => $compBambang->id,
                'effective_date' => '2025-08-01',
            ],
            [
                'employee_id' => $bambang->id,
                'previous_salary' => 3500000,
                'new_salary' => 3850000,
                'increment_amount' => 350000,
                'reason' => 'Evaluasi Siklus 6 Bulan (Kenaikan ke-1)',
            ]
        );

        HcmCompensationHistory::firstOrCreate(
            [
                'compensation_id' => $compBambang->id,
                'effective_date' => '2026-02-01',
            ],
            [
                'employee_id' => $bambang->id,
                'previous_salary' => 3850000,
                'new_salary' => 4200000,
                'increment_amount' => 350000,
                'reason' => 'Evaluasi Siklus 6 Bulan (Kenaikan ke-2)',
            ]
        );

        // 2. Puji Astuti (Kontrak / PKWT Lanjutan)
        $puji = HcmEmployee::updateOrCreate(
            ['nik_ktp' => '3524012010020002'],
            [
                'employee_code' => 'EMP-2026-002',
                'name' => 'Puji Astuti',
                'nickname' => 'Puji',
                'department' => 'Produksi',
                'position' => 'Potong Bahan',
                'job_level' => 'Kontrak',
                'employment_status' => 'PKWT Lanjutan',
                'legal_entity' => 'CV Bawang Putih',
                'phone_number' => '085234567891',
                'gender' => 'Laki Laki',
                'religion' => 'Islam',
                'education' => 'SMA Sederajat',
                'marital_status' => 'Belum Menikah',
                'birth_place' => 'Lamongan',
                'birth_date' => '2002-10-20',
                'shirt_size' => 'L',
                'address' => 'Lamongan, Jawa Timur',
                'bank_account_no' => '0011-01-098765-50-2',
                'bank_name' => 'Bank BRI',
                'join_date' => '2025-08-01',
                'is_active' => true,
            ]
        );

        HcmContract::updateOrCreate(
            ['contract_number' => '002/OWR/PKWT/X/2026'],
            [
                'employee_id' => $puji->id,
                'contract_sequence' => 2,
                'employment_status' => 'PKWT Lanjutan',
                'position' => 'Potong Bahan',
                'legal_entity' => 'CV Bawang Putih',
                'duration_text' => '2 Tahun',
                'trainee_start_month' => 'Agustus',
                'trainee_end_month' => 'Oktober',
                'contract_month' => 'Oktober',
                'start_year' => 2026,
                'start_date' => '2025-08-01',
                'end_date' => '2027-08-01',
                'review_status' => 'Aktif',
            ]
        );

        HcmCompensation::updateOrCreate(
            ['employee_id' => $puji->id],
            [
                'employment_status' => 'PKWT Lanjutan',
                'legal_entity' => 'CV Bawang Putih',
                'contract_number' => '002/OWR/PKWT/X/2026',
                'duration_text' => '2 Tahun',
                'trainee_duration_months' => 12,
                'evaluation_cycle_months' => 6,
                'initial_salary' => 2500000,
                'current_salary' => 2800000,
                'salary_increment_count' => 1,
                'increment_1_amount' => 300000,
                'salary_status' => 'Telah Berlaku',
            ]
        );

        // 3. Danang (Borongan / PKWT)
        $danang = HcmEmployee::updateOrCreate(
            ['nik_ktp' => '3524012010030003'],
            [
                'employee_code' => 'EMP-2026-003',
                'name' => 'Danang',
                'nickname' => 'Danang',
                'department' => 'Produksi',
                'position' => 'Jahit',
                'job_level' => 'Borongan',
                'employment_status' => 'PKWT',
                'legal_entity' => 'CV Bawang Putih',
                'phone_number' => '085234567892',
                'gender' => 'Laki Laki',
                'religion' => 'Islam',
                'education' => 'SMA Sederajat',
                'marital_status' => 'Belum Menikah',
                'birth_place' => 'Lamongan',
                'birth_date' => '2003-10-20',
                'shirt_size' => 'M',
                'address' => 'Lamongan, Jawa Timur',
                'bank_account_no' => '0011-01-098765-50-3',
                'bank_name' => 'Bank BRI',
                'join_date' => '2026-01-01',
                'is_active' => true,
            ]
        );

        HcmContract::updateOrCreate(
            ['contract_number' => '003/OWR/PKWT/X/2026'],
            [
                'employee_id' => $danang->id,
                'contract_sequence' => 1,
                'employment_status' => 'PKWT',
                'position' => 'Jahit',
                'legal_entity' => 'CV Bawang Putih',
                'duration_text' => '1 Tahun',
                'trainee_start_month' => 'Agustus',
                'trainee_end_month' => 'Oktober',
                'contract_month' => 'November',
                'start_year' => 2026,
                'start_date' => '2026-01-01',
                'end_date' => '2027-01-01',
                'review_status' => 'Aktif',
            ]
        );

        HcmCompensation::updateOrCreate(
            ['employee_id' => $danang->id],
            [
                'employment_status' => 'PKWT',
                'legal_entity' => 'CV Bawang Putih',
                'contract_number' => '003/OWR/PKWT/X/2026',
                'duration_text' => '1 Tahun',
                'trainee_duration_months' => 8,
                'evaluation_cycle_months' => 4,
                'initial_salary' => 2200000,
                'current_salary' => 2200000,
                'salary_increment_count' => 0,
                'salary_status' => 'Telah Berlaku',
            ]
        );

        // 4. Peserta Magang SMK 2 Lamongan
        $magang = HcmEmployee::updateOrCreate(
            ['nik_ktp' => '3524011504080004'],
            [
                'employee_code' => 'INT-2026-001',
                'name' => 'Siti Nurhaliza',
                'nickname' => 'Siti',
                'department' => 'Produksi',
                'position' => 'Jahit',
                'job_level' => 'Magang',
                'employment_status' => 'Magang',
                'legal_entity' => 'CV Bawang Merah',
                'phone_number' => '085712345678',
                'gender' => 'Perempuan',
                'religion' => 'Islam',
                'education' => 'SMA Sederajat',
                'marital_status' => 'Belum Menikah',
                'birth_place' => 'Lamongan',
                'birth_date' => '2008-04-15',
                'shirt_size' => 'M',
                'address' => 'Dusun Kebonagung, Kec. Sukodadi, Kab. Lamongan',
                'join_date' => '2026-07-31',
                'is_active' => true,
            ]
        );

        HcmIntern::updateOrCreate(
            ['employee_id' => $magang->id],
            [
                'school_name' => 'SMK 2 Lamongan',
                'class' => 'XII',
                'major' => 'Tata Busana',
                'nis' => '2024.12.045',
                'start_date' => '2026-07-31',
                'end_date' => '2026-10-30',
                'duration_text' => '3 bulan',
                'mentor_teacher_name' => 'Bu. Ningsih',
                'mentor_teacher_phone' => '081234567899',
            ]
        );
    }
}
