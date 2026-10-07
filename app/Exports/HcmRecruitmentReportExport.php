<?php

namespace App\Exports;

use App\Exports\Sheets\Hcm\HcmRecruitmentApplicantsSheet;
use App\Exports\Sheets\Hcm\HcmRecruitmentJobsSheet;
use App\Exports\Sheets\Hcm\HcmRecruitmentSummarySheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HcmRecruitmentReportExport implements WithMultipleSheets
{
    public function __construct(
        private Collection $rows,
        private ?object $selectedJob = null,
        private Collection $applicants = new Collection(),
        private ?string $userName = null,
        private array $channels = []
    ) {}

    public function sheets(): array
    {
        return [
            // Sheet 1: Ringkasan Eksekutif & KPI
            new HcmRecruitmentSummarySheet(
                $this->rows,
                $this->selectedJob,
                $this->applicants,
                $this->userName,
                $this->channels
            ),

            // Sheet 2: Data Lowongan Kerja (Job Postings)
            new HcmRecruitmentJobsSheet($this->rows),

            // Sheet 3: Database Kandidat Pelamar & Wawancara
            new HcmRecruitmentApplicantsSheet($this->applicants, $this->selectedJob),
        ];
    }
}
