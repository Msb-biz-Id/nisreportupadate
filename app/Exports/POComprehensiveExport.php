<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class POComprehensiveExport implements WithMultipleSheets
{
    public function __construct(
        private string $title,
        private array $orders
    ) {}

    public function sheets(): array
    {
        return [
            new GenericReportExport('PO Summary', [
                ['key' => 'no_po', 'label' => 'No PO'],
                ['key' => 'nama_po', 'label' => 'Nama PO'],
                ['key' => 'brand', 'label' => 'Brand'],
                ['key' => 'pelanggan', 'label' => 'Pelanggan'],
                ['key' => 'tanggal_masuk', 'label' => 'Tgl Masuk'],
                ['key' => 'deadline', 'label' => 'Deadline'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'total_tagihan', 'label' => 'Total Tagihan'],
                ['key' => 'is_lunas', 'label' => 'Status Pelunasan'],
            ], $this->getOrderSummaryRows()),

            new GenericReportExport('Progress Details', [
                ['key' => 'no_po', 'label' => 'No PO'],
                ['key' => 'nama_po', 'label' => 'Nama PO'],
                ['key' => 'tahapan', 'label' => 'Tahapan Progress'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'started_at', 'label' => 'Mulai Pada'],
                ['key' => 'completed_at', 'label' => 'Selesai Pada'],
                ['key' => 'catatan', 'label' => 'Catatan'],
                ['key' => 'kendala', 'label' => 'Kendala'],
            ], $this->getProgressRows()),

            new GenericReportExport('Rijek Records', [
                ['key' => 'no_po', 'label' => 'No PO'],
                ['key' => 'nama_po', 'label' => 'Nama PO'],
                ['key' => 'tahapan', 'label' => 'Tahapan'],
                ['key' => 'jenis', 'label' => 'Jenis Rijek'],
                ['key' => 'tingkat', 'label' => 'Tingkat'],
                ['key' => 'jumlah', 'label' => 'Jumlah (pcs)'],
                ['key' => 'kendala', 'label' => 'Kendala'],
                ['key' => 'status', 'label' => 'Status Penanganan'],
            ], $this->getRijekRows()),

            new GenericReportExport('Payment Records', [
                ['key' => 'no_po', 'label' => 'No PO'],
                ['key' => 'nama_po', 'label' => 'Nama PO'],
                ['key' => 'tipe', 'label' => 'Tipe Pembayaran'],
                ['key' => 'nominal', 'label' => 'Nominal'],
                ['key' => 'tanggal', 'label' => 'Tanggal Bayar'],
                ['key' => 'bank', 'label' => 'Bank Penerima'],
                ['key' => 'status', 'label' => 'Status Verifikasi'],
                ['key' => 'catatan', 'label' => 'Catatan'],
            ], $this->getPaymentRows()),
        ];
    }

    private function getOrderSummaryRows(): array
    {
        return collect($this->orders)->map(fn($o) => [
            'no_po' => $o->no_po,
            'nama_po' => $o->nama_po,
            'brand' => $o->brand?->nama_brand,
            'pelanggan' => $o->pelanggan?->nama,
            'tanggal_masuk' => $o->tanggal_masuk?->toDateString(),
            'deadline' => $o->deadline_customer?->toDateString(),
            'status' => $o->status_po,
            'total_tagihan' => (float)$o->total_tagihan,
            'is_lunas' => $o->is_lunas ? 'Lunas' : 'Belum Lunas',
        ])->all();
    }

    private function getProgressRows(): array
    {
        $rows = [];
        foreach ($this->orders as $o) {
            foreach ($o->progressDetails as $pd) {
                $rows[] = [
                    'no_po' => $o->no_po,
                    'nama_po' => $o->nama_po,
                    'tahapan' => $pd->progress?->nama_progress ?? '-',
                    'status' => $pd->status,
                    'started_at' => $pd->started_at?->toDateTimeString() ?? '-',
                    'completed_at' => $pd->completed_at?->toDateTimeString() ?? '-',
                    'catatan' => $pd->catatan ?? '-',
                    'kendala' => $pd->kendala ?? '-',
                ];
            }
        }
        return $rows;
    }

    private function getRijekRows(): array
    {
        $rows = [];
        foreach ($this->orders as $o) {
            foreach ($o->rijeks as $rj) {
                $rows[] = [
                    'no_po' => $o->no_po,
                    'nama_po' => $o->nama_po,
                    'tahapan' => $rj->progress?->nama_progress ?? '-',
                    'jenis' => $rj->jenis,
                    'tingkat' => $rj->tingkat,
                    'jumlah' => $rj->jumlah,
                    'kendala' => $rj->kendala ?? '-',
                    'status' => $rj->status,
                ];
            }
        }
        return $rows;
    }

    private function getPaymentRows(): array
    {
        $rows = [];
        foreach ($this->orders as $o) {
            foreach ($o->payments as $pm) {
                $rows[] = [
                    'no_po' => $o->no_po,
                    'nama_po' => $o->nama_po,
                    'tipe' => $pm->masterJenisPembayaran?->nama ?? $pm->payment_type,
                    'nominal' => (float)$pm->amount,
                    'tanggal' => $pm->payment_date?->toDateString() ?? '-',
                    'bank' => $pm->bank?->nama_bank ?? '-',
                    'status' => $pm->verified_at ? 'Terverifikasi' : 'Pending',
                    'catatan' => $pm->notes ?? '-',
                ];
            }
        }
        return $rows;
    }
}
