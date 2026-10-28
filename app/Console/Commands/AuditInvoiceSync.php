<?php

namespace App\Console\Commands;

use App\Models\Order\Invoice;
use App\Models\Order\Order;
use Illuminate\Console\Command;

class AuditInvoiceSync extends Command
{
    protected $signature = 'invoice:audit-sync
        {--brand= : Filter id brand (default semua brand)}
        {--fix : Resync total_tagihan / total_bayar / sisa_pembayaran / status seluruh invoice dari data live order}
        {--clean-orphans : Soft-delete invoice yang order-nya sudah tidak ada/soft-delete (belum lunas)}
        {--sample=10 : Jumlah contoh baris per kategori}';

    protected $description = 'Audit perbedaan antara widget Reminder Sisa PO (basis orders, hitungan live) dengan daftar Invoice Belum Lunas (basis invoices, kolom tersimpan)';

    private const TOLERANSI = 1.0;

    public function handle(): int
    {
        $brandId = $this->option('brand') ?: null;

        $this->info('=== AUDIT SINKRONISASI REMINDER PO vs INVOICE BELUM LUNAS ===');

        $orders = Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->with(['invoices', 'items', 'payments.masterJenisPembayaran', 'pelanggan:id,nama'])
            ->get();

        $live = [];
        foreach ($orders as $o) {
            $tagihan = $o->totalTagihan();
            $paid = $o->totalPaid();
            $live[$o->id] = [
                'order' => $o,
                'sisa' => max(0.0, $tagihan - $paid),
            ];
        }

        // Basis 1: widget Reminder Sisa PO (sama dengan DashboardService::reminderSisaPo)
        $widgetRows = collect($live)->filter(
            fn ($r) => $r['order']->status_po !== 'draft'
                && !$r['order']->is_special_order
                && !$r['order']->is_lunas
                && $r['sisa'] > self::TOLERANSI
        );
        $widgetCount = $widgetRows->count();
        $widgetSum = $widgetRows->sum('sisa');

        // Basis 2: tab Belum Lunas InvoiceController::list
        $belumLunasInvoices = Invoice::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->where('status', '!=', 'paid')
            ->where('sisa_pembayaran', '>', 0)
            ->get();

        $listCount = $belumLunasInvoices->count();
        $listSum = (float) $belumLunasInvoices->sum('sisa_pembayaran');

        // Basis 3: definisi piutang aktif baru (scope OutstandingPiutang — sama persis dg widget)
        $newCount = Invoice::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->outstandingPiutang()
            ->count();
        $newSum = (float) Invoice::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->outstandingPiutang()
            ->sum('sisa_pembayaran');

        // Invoice sisa NEGATIF (overpayment tersimpan) — tak tampil di tab tapi menurunkan kartu /invoices
        $negQ = fn () => Invoice::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->where('status', '!=', 'paid')
            ->where('sisa_pembayaran', '<', 0);
        $negCount = $negQ()->count();
        $negSum = (float) $negQ()->sum('sisa_pembayaran');

        $this->table(
            ['Basis', 'Jumlah', 'Total Sisa'],
            [
                ['Widget Reminder (orders, live)', $widgetCount, $this->rp($widgetSum)],
                ['Invoice List Belum Lunas (lama, tersimpan)', $listCount, $this->rp($listSum)],
                ['Invoice List Belum Lunas (def baru / sudah di-fix)', $newCount, $this->rp($newSum)],
                ['SELISIH (lama vs widget)', $listCount - $widgetCount, $this->rp($listSum - $widgetSum)],
                ['Invoice sisa NEGATIF status != paid (bukan tab)', $negCount, $this->rp($negSum)],
            ]
        );

        // Kategori invoice "belum lunas" yang TIDAK seharusnya dihitung sebagai piutang aktif
        $kat = $this->emptyKat();
        $samples = [];

        foreach ($belumLunasInvoices as $inv) {
            $order = $live[$inv->order_id] ?? null;

            if (!$order) {
                $this->addKat($kat, $samples, 'A1', $inv, 'Order tidak ada / sudah di-soft delete', null);
                continue;
            }

            $o = $order['order'];
            $liveSisa = $order['sisa'];

            if ($o->status_po === 'draft') {
                $this->addKat($kat, $samples, 'A2', $inv, 'Order masih DRAFT (tidak dihitung widget)', $liveSisa);
            } elseif ($o->is_special_order) {
                $this->addKat($kat, $samples, 'A3', $inv, 'Order special/support (dikecualikan widget)', $liveSisa);
            } elseif ($inv->status === 'cancel') {
                $this->addKat($kat, $samples, 'A4', $inv, 'Invoice berstatus CANCEL', $liveSisa);
            } elseif ($o->is_lunas) {
                $this->addKat($kat, $samples, 'A5', $inv, 'Order sudah ditandai LUNAS manual, invoice ikut tertinggal', $liveSisa);
            } elseif (abs((float) $inv->sisa_pembayaran - $liveSisa) > self::TOLERANSI) {
                $this->addKat($kat, $samples, 'A6', $inv, 'Sisa tersimpan STALE vs live', $liveSisa);
            } else {
                $this->addKat($kat, $samples, 'OK', $inv, 'Cocok dengan live', $liveSisa);
            }
        }

        // Order yang dihitung widget tapi tidak muncul di list invoice
        $b1 = ['count' => 0, 'sum' => 0.0];
        $b2 = ['count' => 0, 'sum' => 0.0];
        $multi = [];

        foreach ($widgetRows as $r) {
            /** @var Order $o */
            $o = $r['order'];
            $invCount = $o->invoices->count();

            if ($invCount === 0) {
                $b1['count']++;
                $b1['sum'] += $r['sisa'];
            } elseif ($o->invoices->every(
                fn ($i) => $i->status === 'paid' || (float) $i->sisa_pembayaran <= self::TOLERANSI
            )) {
                $b2['count']++;
                $b2['sum'] += $r['sisa'];
            }

            if ($invCount > 1) {
                $multi[] = $o->no_po . ' (' . $invCount . ' invoice)';
            }
        }

        $this->info('--- Rincian penyebab invoice list > widget ---');
        $desc = [
            'A1' => 'Invoice yatim (order soft-delete)',
            'A2' => 'Invoice order DRAFT',
            'A3' => 'Invoice order special/support',
            'A4' => 'Invoice status CANCEL',
            'A5' => 'Order ditandai lunas manual (markLunas tidak sync invoice)',
            'A6' => 'Sisa tersimpan stale vs perhitungan live',
        ];
        foreach ($desc as $code => $label) {
            $this->line(sprintf(
                '  %s %-55s : %4d invoice  %s',
                $code,
                $label,
                $kat[$code]['count'],
                $this->rp($kat[$code]['sum'])
            ));
        }
        $this->line('  OK  Piutang aktif cocok (di kedua sisi)               : '
            . str_pad((string) $kat['OK']['count'], 4) . ' invoice  ' . $this->rp($kat['OK']['sum']));

        $this->info('--- Penyebab widget > invoice list ---');
        $this->line(sprintf('  B1 %-52s : %4d PO  %s', 'Order belum lunas TANPA invoice', $b1['count'], $this->rp($b1['sum'])));
        $this->line(sprintf('  B2 %-52s : %4d PO  %s', 'Order belum lunas tapi invoice terbit paid/sisa 0', $b2['count'], $this->rp($b2['sum'])));
        if ($multi) {
            $this->warn('  Beberapa order punya lebih dari 1 invoice: ' . implode(', ', array_slice($multi, 0, 15)));
        }

        // Rekonsiliasi
        $piutangBenar = $widgetSum;
        $this->info('--- Kesimpulan kuantitatif ---');
        $this->line('  Angka piutang benar (live, per order)      : ' . $this->rp($piutangBenar) . " ({$widgetCount} PO)");
        $this->line('  Angka invoice list saat ini                : ' . $this->rp($listSum) . " ({$listCount} invoice)");
        $noise = $listSum - $kat['OK']['sum'];
        $this->line('  Sampah di invoice list (A1-A6)             : ' . $this->rp($noise));
        $this->line('  Piutang yang belum tertagih invoice (B1+B2): ' . $this->rp($b1['sum'] + $b2['sum']));

        $sampleLimit = (int) $this->option('sample');
        foreach ($samples as $code => $rows) {
            if (empty($rows) || $code === 'OK') {
                continue;
            }
            $this->info("  Contoh {$code}:");
            $this->table(
                ['No PO', 'Invoice', 'Sisa Tersimpan', 'Sisa Live'],
                array_slice($rows, 0, $sampleLimit)
            );
        }

        if ($this->option('clean-orphans')) {
            $orphans = Invoice::query()
                ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
                ->where('status', '!=', 'paid')
                ->whereDoesntHave('order')
                ->get();
            foreach ($orphans as $inv) {
                $inv->delete();
            }
            $preview = implode(', ', $orphans->pluck('invoice_number')->take(8)->all());
            $this->info('Soft-delete ' . $orphans->count() . ' invoice yatim (order sudah tidak ada). Contoh: ' . $preview);
        }

        if ($this->option('fix')) {
            $this->fix($brandId);
        } else {
            $this->comment('Run read-only. Gunakan --fix untuk resync kolom tersimpan invoice dari data live order, dan --clean-orphans untuk soft-delete invoice yatim.');
        }

        return self::SUCCESS;
    }

    private function fix(?string $brandId): void
    {
        $changed = 0;
        Invoice::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->with(['order.items', 'order.payments.masterJenisPembayaran'])
            ->chunkById(100, function ($invoices) use (&$changed) {
                foreach ($invoices as $inv) {
                    if (!$inv->order) {
                        continue;
                    }
                    $before = [(float) $inv->total_tagihan, (float) $inv->total_bayar, (float) $inv->sisa_pembayaran, $inv->status];
                    $inv->syncWithOrder();
                    $inv->refresh();
                    $after = [(float) $inv->total_tagihan, (float) $inv->total_bayar, (float) $inv->sisa_pembayaran, $inv->status];
                    if ($before !== $after) {
                        $changed++;
                    }
                }
            });
        $this->info("Resync selesai: {$changed} invoice diubah mengikuti perhitungan live order.");
        $this->line('Jalankan ulang `php artisan po:sync-lunas` lalu audit ini untuk verifikasi.');
    }

    private function emptyKat(): array
    {
        $k = ['OK' => ['count' => 0, 'sum' => 0.0]];
        foreach (['A1', 'A2', 'A3', 'A4', 'A5', 'A6'] as $c) {
            $k[$c] = ['count' => 0, 'sum' => 0.0];
        }
        return $k;
    }

    private function addKat(array &$kat, array &$samples, string $code, Invoice $inv, string $label, ?float $liveSisa): void
    {
        $kat[$code]['count']++;
        $kat[$code]['sum'] += (float) $inv->sisa_pembayaran;
        $samples[$code][] = [
            $inv->order?->no_po ?? '(yatim)',
            $inv->invoice_number,
            $this->rp((float) $inv->sisa_pembayaran),
            $liveSisa === null ? '-' : $this->rp($liveSisa),
        ];
    }

    private function rp(float $v): string
    {
        return 'Rp ' . number_format($v, 0, ',', '.');
    }
}
