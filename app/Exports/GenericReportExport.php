<?php

namespace App\Exports;

use App\Exports\Sheets\ReportDetailSheet;
use App\Exports\Sheets\ReportSummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GenericReportExport implements WithMultipleSheets
{
    public function __construct(
        private string $title,
        private array $columns,
        private array $rows,
        private string $primaryColor = '1E40AF',
        private ?array $summaryTable = null,
        private ?array $brandSummaryTable = null,
        private array $filters = [],
        private ?string $brandName = null,
        private ?string $userName = null
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        // Sheet 1: Ringkasan (Kategori Harga & Detail Per Brand Secara Jumlah)
        if (!empty($this->summaryTable) || !empty($this->brandSummaryTable)) {
            $sheets[] = new ReportSummarySheet(
                title: $this->title,
                primaryColor: $this->primaryColor,
                summaryTable: $this->summaryTable,
                brandSummaryTable: $this->brandSummaryTable,
                filters: $this->filters,
                brandName: $this->brandName,
                userName: $this->userName
            );
        }

        // Sheet 2: Detail Data Transaksi
        $sheets[] = new ReportDetailSheet(
            title: $this->title,
            columns: $this->columns,
            rows: $this->rows,
            primaryColor: $this->primaryColor,
            filters: $this->filters,
            brandName: $this->brandName,
            userName: $this->userName,
            sheetTitle: !empty($sheets) ? 'Detail Data' : $this->title
        );

        return $sheets;
    }
}
