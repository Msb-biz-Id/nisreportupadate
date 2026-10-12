<?php

namespace App\Exports\Purchasing;

use App\Models\Purchasing\PurchasingOrder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchasingReportExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Rekap Pengadaan';
    }

    public function headings(): array
    {
        return [
            ['LAPORAN REKAPITULASI PENGADAAN & PEMBELIAN (PURCHASING)'],
            ['Periode: ' . ($this->filters['period_label'] ?? 'Semua Waktu')],
            [''],
            [
                'No',
                'No. PO',
                'Tanggal',
                'Jenis Biaya',
                'Departemen',
                'Divisi',
                'Pemohon',
                'Supplier / Vendor',
                'Kategori Item',
                'Status Transaksi',
                'Status Bayar',
                'Subtotal (Rp)',
                'PPN (Rp)',
                'Ongkir (Rp)',
                'Grand Total (Rp)',
            ],
        ];
    }

    public function array(): array
    {
        $query = PurchasingOrder::with(['vendor', 'location', 'payments'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['order_type']) && $this->filters['order_type'] !== 'ALL') {
            $query->where('order_type', $this->filters['order_type']);
        }
        if (!empty($this->filters['department'])) {
            $query->where('department', $this->filters['department']);
        }
        if (!empty($this->filters['division'])) {
            $query->where('division', $this->filters['division']);
        }
        if (!empty($this->filters['location_id'])) {
            $query->where('location_id', $this->filters['location_id']);
        }

        $orders = $query->get();
        $rows = [];
        $no = 1;

        foreach ($orders as $order) {
            $totalPaid = $order->payments->where('status', 'PAID')->sum('amount');
            $payStatus = 'Belum Lunas';
            if ($totalPaid >= (float) $order->grand_total && (float) $order->grand_total > 0) {
                $payStatus = 'Lunas';
            } elseif ($totalPaid > 0) {
                $payStatus = 'Dibayar Sebagian';
            }

            $rows[] = [
                $no++,
                $order->po_number,
                $order->transaction_date?->format('d/m/Y'),
                $order->order_type,
                $order->department,
                $order->division,
                $order->requester_name ?: '-',
                $order->vendor?->name ?: ($order->vendor_name_manual ?: '-'),
                $order->item_category,
                $order->status,
                $payStatus,
                (float) $order->subtotal,
                (float) $order->tax_amount,
                (float) $order->shipping_cost,
                (float) $order->grand_total,
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'L' => '#,##0',
            'M' => '#,##0',
            'N' => '#,##0',
            'O' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        // Judul Laporan
        $sheet->mergeCells('A1:O1');
        $sheet->mergeCells('A2:O2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);

        // Header Table di Baris 4
        $sheet->getStyle('A4:O4')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:O4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle('A4:O4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Border keseluruhan tabel
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:O{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        }

        return [];
    }
}
