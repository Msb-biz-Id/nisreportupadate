<?php

namespace App\Exports;

use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmLeaveRequest;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmAttendanceDailyExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $dataStartRow = 7;
    private int $totalRow = 0;

    public function __construct(
        private string $date,
        private string $category = 'all',
        private string $department = 'all',
        private ?string $userName = null
    ) {}

    public function array(): array
    {
        $output = [];
        $carbonDate = Carbon::parse($this->date);

        // 1. Query Employees
        $empQuery = HcmEmployee::where('is_active', true)
            ->when($this->category === 'REGULAR', fn ($q) => $q->regular())
            ->when($this->category === 'INTERN', fn ($q) => $q->interns())
            ->when($this->department !== 'all', fn ($q) => $q->where('department', $this->department))
            ->orderBy('department')
            ->orderBy('employee_category')
            ->orderBy('name');

        $employees = $empQuery->get();
        $employeeIds = $employees->pluck('id');

        // 2. Fetch Attendances on this date
        $attendances = HcmAttendance::where('attendance_date', $this->date)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        // 3. Fetch Leaves on this date
        $leaves = HcmLeaveRequest::where('status', 'APPROVED')
            ->where('start_date', '<=', $this->date)
            ->where('end_date', '>=', $this->date)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        // Labels
        $catLabel = match ($this->category) {
            'REGULAR' => 'Karyawan Reguler (Managerial / Kontrak / Borongan)',
            'INTERN' => 'Peserta Magang SMK (PKL)',
            default => 'Semua Kategori Personel',
        };
        $deptLabel = $this->department !== 'all' ? $this->department : 'Semua Departemen';

        // 4. Metadata
        $output[] = ['PT NIS INDONESIA (NISGROUP)'];
        $output[] = ['DAFTAR PRESENSI DAN DISIPLIN HARIAN'];
        $output[] = [
            'Hari, Tanggal: ' . $carbonDate->translatedFormat('l, d F Y') .
            '  |  Kategori: ' . $catLabel .
            '  |  Departemen: ' . $deptLabel
        ];
        $output[] = [
            'Waktu Unduh: ' . now()->translatedFormat('d M Y, H:i') . ' WIB' .
            ($this->userName ? '  |  Petugas: ' . $this->userName : '') .
            '  |  Total Personel: ' . $employees->count() . ' Orang'
        ];
        $output[] = ['']; // Empty row

        // 5. Table Header
        $output[] = [
            'No',
            'NIK',
            'Nama Karyawan',
            'Kategori',
            'Departemen',
            'Jabatan',
            'Status Kehadiran',
            'Jam Masuk',
            'Jam Pulang',
            'Terlambat (Mnt)',
            'Lembur (Jam)',
            'Catatan / Alasan',
        ];

        // 6. Data Rows
        $no = 1;
        $cntHadir = 0;
        $cntTerlambat = 0;
        $cntIzin = 0;
        $cntSakit = 0;
        $cntCuti = 0;
        $cntAlpha = 0;
        $cntBelum = 0;
        $totalLateMin = 0;
        $totalOvertime = 0.0;

        foreach ($employees as $emp) {
            $att = $attendances->get($emp->id);
            $leave = $leaves->get($emp->id);

            $statusText = 'Belum Diabsen';
            $checkIn = '-';
            $checkOut = '-';
            $lateMinutes = 0;
            $overtimeHours = 0.0;
            $notes = '';

            if ($att) {
                $statusText = match ($att->attendance_category) {
                    'PRESENT' => 'Hadir Tepat',
                    'LATE' => 'Terlambat',
                    'PERMIT' => 'Izin',
                    'SICK' => 'Sakit',
                    'LEAVE' => 'Cuti',
                    'ALPHA' => 'Alpha',
                    default => $att->attendance_category,
                };
                $checkIn = $att->check_in ? Carbon::parse($att->check_in)->format('H:i') : '-';
                $checkOut = $att->check_out ? Carbon::parse($att->check_out)->format('H:i') : '-';
                $lateMinutes = (int) ($att->late_minutes ?? 0);
                $overtimeHours = (float) ($att->overtime_hours ?? 0);
                $notes = $att->notes ?? '';

                if ($att->attendance_category === 'PRESENT') $cntHadir++;
                elseif ($att->attendance_category === 'LATE') $cntTerlambat++;
                elseif ($att->attendance_category === 'PERMIT') $cntIzin++;
                elseif ($att->attendance_category === 'SICK') $cntSakit++;
                elseif ($att->attendance_category === 'LEAVE') $cntCuti++;
                elseif ($att->attendance_category === 'ALPHA') $cntAlpha++;
            } elseif ($leave) {
                $statusText = match ($leave->leave_type) {
                    'SAKIT' => 'Sakit (Cuti)',
                    'IZIN' => 'Izin (Cuti)',
                    default => 'Cuti Tahunan',
                };
                $notes = $leave->reason ?? 'Disetujui di Portal HCM';
                if ($leave->leave_type === 'SAKIT') $cntSakit++;
                elseif ($leave->leave_type === 'IZIN') $cntIzin++;
                else $cntCuti++;
            } else {
                $cntBelum++;
            }

            $totalLateMin += $lateMinutes;
            $totalOvertime += $overtimeHours;

            $output[] = [
                $no++,
                $emp->employee_code ?? '-',
                $emp->name,
                $emp->employee_category === 'INTERN' ? 'Magang SMK' : 'Reguler',
                $emp->department ?? '-',
                $emp->designation ?? $emp->job_level ?? '-',
                $statusText,
                $checkIn,
                $checkOut,
                $lateMinutes > 0 ? $lateMinutes : '-',
                $overtimeHours > 0 ? $overtimeHours : '-',
                $notes,
            ];
        }

        // 7. Total Row
        $summaryText = "TOTAL: {$employees->count()} Personel (Hadir: {$cntHadir}, Telat: {$cntTerlambat}, Izin: {$cntIzin}, Sakit: {$cntSakit}, Cuti: {$cntCuti}, Alpha: {$cntAlpha}, Belum: {$cntBelum})";
        $totalRow = [
            $summaryText, '', '', '', '', '', '', '', '',
            $totalLateMin > 0 ? $totalLateMin : '-',
            $totalOvertime > 0 ? round($totalOvertime, 1) : '-',
            '',
        ];
        $output[] = $totalRow;
        $this->totalRow = count($output);

        return $output;
    }

    public function title(): string
    {
        return 'Presensi ' . Carbon::parse($this->date)->format('d-m-Y');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestCol = 'L';
                $totalRowIdx = $this->totalRow;

                // Title Metadata Styling
                $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0F172A']],
                ]);
                $sheet->getStyle("A2:{$highestCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E40AF']],
                ]);
                $sheet->getStyle("A3:{$highestCol}3")->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '334155']],
                ]);
                $sheet->getStyle("A4:{$highestCol}4")->applyFromArray([
                    'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '64748B']],
                ]);

                // Header Row (Row 6)
                $sheet->getStyle("A{$this->headerRow}:{$highestCol}{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Data Rows
                if ($totalRowIdx >= $this->dataStartRow) {
                    $dataEndRow = $totalRowIdx - 1;

                    $sheet->getStyle("A{$this->headerRow}:{$highestCol}{$dataEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    $sheet->getStyle("A{$this->dataStartRow}:B{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$this->dataStartRow}:F{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("G{$this->dataStartRow}:K{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("L{$this->dataStartRow}:L{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Total Row
                $sheet->getStyle("A{$totalRowIdx}:{$highestCol}{$totalRowIdx}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0'],
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '475569']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->mergeCells("A{$totalRowIdx}:I{$totalRowIdx}");
                $sheet->getStyle("A{$totalRowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }
}
