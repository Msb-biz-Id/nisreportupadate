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

class HcmAttendanceMonthlyMatrixExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $dataStartRow = 7;
    private int $totalRow = 0;
    private array $weekendColIndexes = [];
    private int $dayStartColIdx = 7; // G
    private int $dayEndColIdx = 7;
    private int $summaryStartColIdx = 8;
    private int $lastColIdx = 8;

    public function __construct(
        private string $month,
        private string $category = 'all',
        private string $department = 'all',
        private ?string $userName = null
    ) {}

    public function array(): array
    {
        $output = [];

        $carbonMonth = Carbon::parse($this->month . '-01');
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        // 1. Employees Query
        $empQuery = HcmEmployee::where('is_active', true)
            ->when($this->category === 'REGULAR', fn ($q) => $q->regular())
            ->when($this->category === 'INTERN', fn ($q) => $q->interns())
            ->when($this->department !== 'all', fn ($q) => $q->where('department', $this->department))
            ->orderBy('department')
            ->orderBy('employee_category')
            ->orderBy('name');

        $employees = $empQuery->get();
        $employeeIds = $employees->pluck('id');

        // 2. Fetch Attendances & Leaves
        $attendances = HcmAttendance::whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $leaves = HcmLeaveRequest::where('status', 'APPROVED')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhereBetween('end_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                        $sub->where('start_date', '<=', $startOfMonth->toDateString())
                            ->where('end_date', '>=', $endOfMonth->toDateString());
                    });
            })
            ->whereIn('employee_id', $employeeIds)
            ->get();

        // Index attendances
        $attKeyed = [];
        foreach ($attendances as $att) {
            $dayNum = (int) Carbon::parse($att->attendance_date)->format('j');
            $attKeyed[$att->employee_id][$dayNum] = [
                'category' => $att->attendance_category,
                'overtime' => (float) ($att->overtime_hours ?? 0),
            ];
        }

        // Index leaves
        $leaveKeyed = [];
        foreach ($leaves as $lv) {
            $lvStart = Carbon::parse($lv->start_date);
            $lvEnd = Carbon::parse($lv->end_date);
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $checkDate = $startOfMonth->copy()->day($d);
                if ($checkDate->betweenIncluded($lvStart, $lvEnd)) {
                    $leaveCode = match ($lv->leave_type) {
                        'SAKIT' => 'S',
                        'IZIN' => 'I',
                        default => 'C',
                    };
                    $leaveKeyed[$lv->employee_id][$d] = $leaveCode;
                }
            }
        }

        // Days metadata
        $effectiveWorkDays = 0;
        $this->weekendColIndexes = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currDate = $startOfMonth->copy()->day($d);
            $isWeekend = $currDate->isWeekend();
            if ($isWeekend) {
                $this->weekendColIndexes[] = $this->dayStartColIdx + ($d - 1);
            } else {
                $effectiveWorkDays++;
            }
        }
        $effectiveWorkDays = max(1, $effectiveWorkDays);
        $this->dayEndColIdx = $this->dayStartColIdx + $daysInMonth - 1;
        $this->summaryStartColIdx = $this->dayEndColIdx + 1;
        $this->lastColIdx = $this->summaryStartColIdx + 7; // H, T, I, S, C, A, Lembur, %

        // Category label
        $catLabel = match ($this->category) {
            'REGULAR' => 'Karyawan Reguler (Managerial / Kontrak / Borongan)',
            'INTERN' => 'Peserta Magang SMK (PKL)',
            default => 'Semua Kategori Personel',
        };

        $deptLabel = $this->department !== 'all' ? $this->department : 'Semua Departemen';

        // 3. Metadata Header (Rows 1 - 5)
        $output[] = ['PT NIS INDONESIA (NISGROUP)'];
        $output[] = ['MATRIKS PRESENSI DAN DISIPLIN BULANAN'];
        $output[] = [
            'Periode: ' . $carbonMonth->translatedFormat('F Y') .
            '  |  Kategori: ' . $catLabel .
            '  |  Departemen: ' . $deptLabel .
            '  |  Hari Kerja Efektif: ' . $effectiveWorkDays . ' Hari'
        ];
        $output[] = [
            'Waktu Unduh: ' . now()->translatedFormat('d M Y, H:i') . ' WIB' .
            ($this->userName ? '  |  Petugas: ' . $this->userName : '') .
            '  |  Total Personel: ' . $employees->count() . ' Orang'
        ];
        $output[] = ['']; // Empty line

        // 4. Table Header (Row 6)
        $headerCols = ['No', 'NIK', 'Nama Karyawan', 'Kategori', 'Departemen', 'Jabatan'];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $headerCols[] = (string) $d;
        }
        $headerCols[] = 'H';
        $headerCols[] = 'T';
        $headerCols[] = 'I';
        $headerCols[] = 'S';
        $headerCols[] = 'C';
        $headerCols[] = 'A';
        $headerCols[] = 'Lembur (Jam)';
        $headerCols[] = 'Kehadiran (%)';

        $output[] = $headerCols;

        // 5. Data Rows
        $no = 1;
        $sumH = 0; $sumT = 0; $sumI = 0; $sumS = 0; $sumC = 0; $sumA = 0; $sumLembur = 0.0; $sumRates = [];

        foreach ($employees as $emp) {
            $row = [
                $no++,
                $emp->employee_code ?? '-',
                $emp->name,
                $emp->employee_category === 'INTERN' ? 'Magang SMK' : 'Reguler',
                $emp->department ?? '-',
                $emp->designation ?? $emp->job_level ?? '-',
            ];

            $cntH = 0; $cntT = 0; $cntI = 0; $cntS = 0; $cntC = 0; $cntA = 0; $totalOt = 0.0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $currDate = $startOfMonth->copy()->day($d);
                $isWeekend = $currDate->isWeekend();

                $cellVal = '-';
                if (isset($attKeyed[$emp->id][$d])) {
                    $cat = $attKeyed[$emp->id][$d]['category'];
                    $ot = $attKeyed[$emp->id][$d]['overtime'];
                    $totalOt += $ot;

                    if ($cat === 'PRESENT') {
                        $cellVal = 'H';
                        $cntH++;
                    } elseif ($cat === 'LATE') {
                        $cellVal = 'T';
                        $cntT++;
                    } elseif ($cat === 'PERMIT') {
                        $cellVal = 'I';
                        $cntI++;
                    } elseif ($cat === 'SICK') {
                        $cellVal = 'S';
                        $cntS++;
                    } elseif ($cat === 'LEAVE') {
                        $cellVal = 'C';
                        $cntC++;
                    } elseif ($cat === 'ALPHA') {
                        $cellVal = 'A';
                        $cntA++;
                    }
                } elseif (isset($leaveKeyed[$emp->id][$d])) {
                    $cellVal = $leaveKeyed[$emp->id][$d];
                    if ($cellVal === 'S') $cntS++;
                    elseif ($cellVal === 'I') $cntI++;
                    elseif ($cellVal === 'C') $cntC++;
                } else {
                    $cellVal = $isWeekend ? '-' : '';
                }

                $row[] = $cellVal;
            }

            // Summary calculations
            $actualPresent = $cntH + $cntT;
            $rate = round(($actualPresent / $effectiveWorkDays) * 100, 1);
            if ($rate > 100) $rate = 100.0;

            $row[] = $cntH;
            $row[] = $cntT;
            $row[] = $cntI;
            $row[] = $cntS;
            $row[] = $cntC;
            $row[] = $cntA;
            $row[] = $totalOt > 0 ? $totalOt : 0;
            $row[] = $rate . '%';

            $sumH += $cntH;
            $sumT += $cntT;
            $sumI += $cntI;
            $sumS += $cntS;
            $sumC += $cntC;
            $sumA += $cntA;
            $sumLembur += $totalOt;
            $sumRates[] = $rate;

            $output[] = $row;
        }

        // 6. Total Summary Row
        $avgRate = count($sumRates) > 0 ? round(array_sum($sumRates) / count($sumRates), 1) : 0;
        $totalRow = ['TOTAL KESELURUHAN', '', '', '', '', ''];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $totalRow[] = '';
        }
        $totalRow[] = $sumH;
        $totalRow[] = $sumT;
        $totalRow[] = $sumI;
        $totalRow[] = $sumS;
        $totalRow[] = $sumC;
        $totalRow[] = $sumA;
        $totalRow[] = round($sumLembur, 1);
        $totalRow[] = $avgRate . '%';

        $output[] = $totalRow;
        $this->totalRow = count($output);

        // 7. Legend Info
        $output[] = [''];
        $output[] = ['KETERANGAN KODE PRESENSI:'];
        $output[] = ['H = Hadir Tepat Waktu  |  T = Terlambat  |  I = Izin  |  S = Sakit  |  C = Cuti  |  A = Alpha (Tanpa Keterangan)  |  - = Hari Libur / Akhir Pekan'];

        return $output;
    }

    public function title(): string
    {
        return 'Matriks ' . Carbon::parse($this->month . '-01')->format('M Y');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestCol = Coordinate::stringFromColumnIndex($this->lastColIdx);
                $totalRowIdx = $this->totalRow;

                // 1. Title Metadata Styling
                $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0F172A']],
                ]);
                $sheet->getStyle("A2:{$highestCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E40AF']],
                ]);
                $sheet->getStyle("A3:{$highestCol}3")->applyFromArray([
                    'font' => ['size' => 10, 'italic' => false, 'color' => ['rgb' => '334155']],
                ]);
                $sheet->getStyle("A4:{$highestCol}4")->applyFromArray([
                    'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '64748B']],
                ]);

                // 2. Header Row Styling (Row 6)
                $sheet->getStyle("A{$this->headerRow}:{$highestCol}{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'], // Dark Slate
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                // Left align employee name in header row
                $sheet->getStyle("C{$this->headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // 3. Shading Weekend Columns
                foreach ($this->weekendColIndexes as $colIdx) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    // Header weekend background
                    $sheet->getStyle("{$colLetter}{$this->headerRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '334155'], // Darker shade for weekend header
                        ],
                    ]);
                    // Data cells weekend background
                    if ($totalRowIdx >= $this->dataStartRow) {
                        $sheet->getStyle("{$colLetter}{$this->dataStartRow}:{$colLetter}" . ($totalRowIdx - 1))->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F1F5F9'], // Soft gray for weekend cells
                            ],
                            'font' => ['color' => ['rgb' => '94A3B8']],
                        ]);
                    }
                }

                // 4. Data Rows Alignment & Borders
                if ($totalRowIdx >= $this->dataStartRow) {
                    $dataEndRow = $totalRowIdx - 1;

                    // Table border
                    $sheet->getStyle("A{$this->headerRow}:{$highestCol}{$dataEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    // Center alignments
                    $sheet->getStyle("A{$this->dataStartRow}:B{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$this->dataStartRow}:F{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    // Matrix days centering
                    $startDayLetter = Coordinate::stringFromColumnIndex($this->dayStartColIdx);
                    $endDayLetter = Coordinate::stringFromColumnIndex($this->dayEndColIdx);
                    $sheet->getStyle("{$startDayLetter}{$this->dataStartRow}:{$endDayLetter}{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Summary columns styling
                    $sumStartLetter = Coordinate::stringFromColumnIndex($this->summaryStartColIdx);
                    $sheet->getStyle("{$sumStartLetter}{$this->dataStartRow}:{$highestCol}{$dataEndRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FAFC'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                        'font' => ['bold' => true],
                    ]);
                }

                // 5. Total Row Styling
                $sheet->getStyle("A{$totalRowIdx}:{$highestCol}{$totalRowIdx}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0'], // Slate 200
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

                // Merge first 6 columns of total row
                $sheet->mergeCells("A{$totalRowIdx}:F{$totalRowIdx}");
                $sheet->getStyle("A{$totalRowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // 6. Legend Styling
                $legendHeaderRow = $totalRowIdx + 2;
                $legendTextRow = $totalRowIdx + 3;
                $sheet->getStyle("A{$legendHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
                ]);
                $sheet->getStyle("A{$legendTextRow}")->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '64748B']],
                ]);

                // Set column dimensions for matrix days
                for ($col = $this->dayStartColIdx; $col <= $this->dayEndColIdx; $col++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(4.5);
                }
            },
        ];
    }
}
