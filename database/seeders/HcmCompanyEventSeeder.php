<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmCompanyEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class HcmCompanyEventSeeder extends Seeder
{
    /**
     * Run the database seeds for company calendar events and training schedules.
     */
    public function run(): void
    {
        $hcmUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();

        $events = [
            [
                'title' => 'Evaluasi Kinerja Bulanan & Closing Batch Q3',
                'event_type' => 'Acara Perusahaan',
                'organizer_name' => 'Human Capital Management',
                'start_date' => '2026-09-30',
                'end_date' => '2026-09-30',
                'start_time' => '16:00',
                'end_time' => '17:30',
                'location' => 'Aula Serbaguna Pabrik Lamongan',
                'target_audience' => 'Semua Divisi',
                'reminder_days' => 1,
                'description' => 'Evaluasi pencapaian target produksi, rekap presensi, dan penutupan batch pembukuan akhir September.',
                'color_code' => '#3b82f6',
                'is_public' => true,
                'is_annual_recurring' => false,
            ],
            [
                'title' => 'Briefing Awal Bulan Divisi Produksi & Target Oktober',
                'event_type' => 'Rapat Internal',
                'organizer_name' => 'PIC Produksi',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-01',
                'start_time' => '07:30',
                'end_time' => '08:00',
                'location' => 'Area Produksi Lt. 1',
                'target_audience' => 'Produksi',
                'reminder_days' => 1,
                'description' => 'Sinkronisasi urutan prioritas pesanan jersey seragam sekolah dan turnamen Oktober.',
                'color_code' => '#10b981',
                'is_public' => true,
                'is_annual_recurring' => false,
            ],
            [
                'title' => 'Townhall Meeting & Apresiasi Karyawan Q3',
                'event_type' => 'Acara Perusahaan',
                'organizer_name' => 'Direksi & HCM',
                'start_date' => '2026-10-05',
                'end_date' => '2026-10-05',
                'start_time' => '13:00',
                'end_time' => '15:30',
                'location' => 'Resto Tanjung Kodok Hall, Lamongan',
                'target_audience' => 'Semua Divisi',
                'reminder_days' => 3,
                'description' => 'Makan bersama, paparan capaian bisnis oleh Direksi, serta seremonial pembagian reward karyawan teladan.',
                'color_code' => '#8b5cf6',
                'is_public' => true,
                'is_annual_recurring' => false,
            ],
            [
                'title' => 'Pelatihan K3 & Pengoperasian Mesin Jahit Digital Terpadu',
                'event_type' => 'Pelatihan & Training',
                'organizer_name' => 'Supervisor QC & K3',
                'start_date' => '2026-10-12',
                'end_date' => '2026-10-12',
                'start_time' => '09:00',
                'end_time' => '12:00',
                'location' => 'Ruang Training Garment Lt. 2',
                'target_audience' => 'Produksi',
                'reminder_days' => 2,
                'description' => 'Workshop teknik jahit kain elastis spandex dan perawatan berkala pisau mesin obras.',
                'color_code' => '#f59e0b',
                'is_public' => true,
                'is_annual_recurring' => false,
            ],
            [
                'title' => 'Libur Nasional Maulid Nabi Muhammad SAW',
                'event_type' => 'Libur Nasional',
                'organizer_name' => 'Pemerintah RI',
                'start_date' => '2026-09-16',
                'end_date' => '2026-09-16',
                'start_time' => '00:00',
                'end_time' => '23:59',
                'location' => 'Seluruh Unit Kerja',
                'target_audience' => 'Semua Divisi',
                'reminder_days' => 3,
                'description' => 'Hari Libur Resmi Nasional Pemerintah Republik Indonesia.',
                'color_code' => '#ef4444',
                'is_public' => true,
                'is_annual_recurring' => true,
            ],
            [
                'title' => 'Annual Family Gathering & Outbound NIS Group di Karimunjawa',
                'event_type' => 'Acara Perusahaan',
                'organizer_name' => 'Panitia HUT NIS Group',
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-22',
                'start_time' => '06:00',
                'end_time' => '18:00',
                'location' => 'Kepulauan Karimunjawa, Jawa Tengah',
                'target_audience' => 'Semua Divisi',
                'reminder_days' => 7,
                'description' => 'Kegiatan rekreasi tahunan, malam keakraban keluarga besar NIS Group, team building, dan doorprize.',
                'color_code' => '#06b6d4',
                'is_public' => true,
                'is_annual_recurring' => true,
            ],
        ];

        foreach ($events as $event) {
            $event['created_by'] = $hcmUser?->id;
            HcmCompanyEvent::updateOrCreate(
                ['title' => $event['title'], 'start_date' => $event['start_date']],
                $event
            );
        }
    }
}
