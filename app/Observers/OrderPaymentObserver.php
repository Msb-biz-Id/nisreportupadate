<?php

namespace App\Observers;

use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\Pemasukan;
use App\Models\Finance\KategoriPengeluaran;
use App\Models\Finance\Pengeluaran;
use App\Models\Order\OrderPayment;

class OrderPaymentObserver
{
    public function creating(OrderPayment $payment): void
    {
        if (empty($payment->payment_type) && !empty($payment->master_jenis_pembayaran_id)) {
            $master = \App\Models\Finance\MasterJenisPembayaran::find($payment->master_jenis_pembayaran_id);
            if ($master) {
                $map = [
                    'DP' => 'dp',
                    'Pelunasan' => 'pelunasan',
                    'Ongkir' => 'ongkir',
                    'Tambahan Produk' => 'tambahan_produk',
                    'Cashback' => 'cashback',
                    'Return' => 'return',
                    'Lainnya' => 'lainnya',
                ];
                $payment->payment_type = $map[$master->nama] ?? 'lainnya';
            } else {
                $payment->payment_type = 'lainnya';
            }
        } elseif (!empty($payment->payment_type) && empty($payment->master_jenis_pembayaran_id)) {
            $map = [
                'dp' => 'DP',
                'pelunasan' => 'Pelunasan',
                'ongkir' => 'Ongkir',
                'tambahan_produk' => 'Tambahan Produk',
                'cashback' => 'Cashback',
                'return' => 'Return',
                'lainnya' => 'Lainnya',
            ];
            $nama = $map[$payment->payment_type] ?? 'Lainnya';
            $master = \App\Models\Finance\MasterJenisPembayaran::where(['nama' => $nama])->first();
            if ($master) {
                $payment->master_jenis_pembayaran_id = $master->id;
            }
        }

        // Automatically sequence DP
        if ($payment->payment_type === 'dp') {
            $lastDp = OrderPayment::where([
                'order_id' => $payment->order_id,
                'payment_type' => 'dp',
            ])->max('dp_sequence');
            $payment->dp_sequence = ($lastDp ?? 0) + 1;
        }

        // Set is_debit
        if (in_array($payment->payment_type, ['cashback', 'return'])) {
            $payment->is_debit = false;
        } else {
            $payment->is_debit = true;
        }
    }

    public function created(OrderPayment $payment): void
    {
        $order = $payment->order;
        if ($order) {
            $order->update(['total_tagihan' => $order->totalTagihan()]);
            foreach ($order->invoices as $invoice) {
                $invoice->syncWithOrder();
            }
        }

        // Record ledger immediately if verified upon creation (except conversions/refunds)
        if ($payment->verified_at !== null) {
            $notes = $payment->notes ?? '';
            if (!str_contains($notes, 'Konversi Tanda Jadi') && !str_contains($notes, 'Refund otomatis')) {
                $this->recordLedger($payment);
            }
        }

        // Dispatch submitted notification untuk payment baru yang belum verified
        if ($payment->verified_at === null) {
            if ($order) {
                try {
                    $formattedAmount = 'Rp ' . number_format((float) $payment->amount, 0, ',', '.');
                    \App\Services\Notifications\IdealNotificationService::dispatch('payment_submitted', [
                        'no_po' => $order->no_po,
                        'brand_id' => $order->brand_id,
                        'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
                        'nominal' => $formattedAmount,
                        'action_url' => '/invoices/payments/pending',
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to dispatch payment_submitted notification', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function updated(OrderPayment $payment): void
    {
        $order = $payment->order;
        if ($order) {
            $order->update(['total_tagihan' => $order->totalTagihan()]);
            foreach ($order->invoices as $invoice) {
                $invoice->syncWithOrder();
            }
        }

        // If the payment just got verified (verified_at transitioned from null to a timestamp)
        if ($payment->isDirty('verified_at') && $payment->verified_at !== null && $payment->getOriginal('verified_at') === null) {
            $this->recordLedger($payment);

            // Dispatch verified notification
            if ($order) {
                try {
                    $formattedAmount = 'Rp ' . number_format((float) $payment->amount, 0, ',', '.');
                    \App\Services\Notifications\IdealNotificationService::dispatch('payment_verified', [
                        'no_po' => $order->no_po,
                        'brand_id' => $order->brand_id,
                        'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
                        'nominal' => $formattedAmount,
                        'action_url' => '/orders/' . $order->id,
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to dispatch payment_verified notification', [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Auto-publish invoice if the payment is a DP
            if ($payment->payment_type === 'dp' && $order) {
                \Illuminate\Support\Facades\DB::afterCommit(function () use ($payment, $order) {
                    $invoice = $order->invoices()->first();
                    if ($invoice && in_array($invoice->status, ['draft', 'validated'], true)) {
                        try {
                            $invoice->update(['status' => 'published']);
                            \App\Services\ActivityLogger::log('publish', 'invoice', $invoice, "Auto publish invoice {$invoice->invoice_number} via DP payment verification observer");
                            
                            $sidobe = \App\Services\Notifications\SidobeClient::fromSettings();
                            if ($sidobe->isConfigured()) {
                                $waService = new \App\Services\Notifications\InvoiceWhatsappService($sidobe);
                                $invoice->load(['order.pelanggan', 'brand']);
                                $phone = $waService->phoneFromInvoice($invoice);
                                if ($phone !== '') {
                                    $result = $waService->send($invoice, 'new_invoice');
                                    if ($result['success'] && ! ($result['mock'] ?? false)) {
                                        $invoice->update([
                                            'status' => 'sent',
                                            'sent_via' => 'whatsapp',
                                            'sent_at' => now(),
                                        ]);
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to auto publish/send invoice on DP verification observer: ' . $e->getMessage());
                        }
                    }
                });
            }
        }
    }

    public function deleted(OrderPayment $payment): void
    {
        $order = $payment->order;
        if ($order) {
            $order->update(['total_tagihan' => $order->totalTagihan()]);
            foreach ($order->invoices as $invoice) {
                $invoice->syncWithOrder();
            }
        }
    }

    private function recordLedger(OrderPayment $payment): void
    {
        $order = $payment->order;
        if (! $order) return;

        if (! $payment->is_debit) {
            // Record as Pengeluaran
            if (Pengeluaran::where(['source_payment_id' => $payment->id])->exists()) {
                return;
            }

            $kategoriName = $payment->payment_type === 'cashback' ? 'Cashback PO' : 'Refund/Return PO';
            $kategoriDesc = $payment->payment_type === 'cashback' ? 'Cashback dari PO' : 'Pengembalian uang / return dari PO';

            $kategori = KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $order->brand_id, 'nama_kategori' => $kategoriName],
                [
                    'deskripsi' => $kategoriDesc,
                    'is_system'  => true,
                    'is_active'  => true,
                ]
            );

            $label = match ($payment->payment_type) {
                'cashback' => 'Cashback',
                'return'   => 'Refund/Return',
                default    => 'Pengeluaran Lainnya',
            };

            Pengeluaran::create([
                'brand_id'               => $order->brand_id,
                'kategori_pengeluaran_id' => $kategori->id,
                'tanggal'                => $payment->payment_date,
                'nominal'                => $payment->amount,
                'keterangan'             => "{$label} PO {$order->no_po} — {$order->nama_po}",
                'is_auto'                => true,
                'created_by'             => $payment->recorded_by,
                'source_payment_id'      => $payment->id,
            ]);
        } else {
            // Record as Pemasukan
            if (Pemasukan::where(['source_payment_id' => $payment->id])->exists()) {
                return;
            }

            $label = match ($payment->payment_type) {
                'dp'               => 'DP ' . ($payment->dp_sequence ?? 1),
                'pelunasan'        => 'Pelunasan',
                'ongkir'           => 'Ongkir',
                'tambahan_produk'  => 'Tambahan Produk',
                default            => 'Pembayaran Lainnya',
            };

            $kategori = KategoriPemasukan::firstOrCreate(
                ['brand_id' => $order->brand_id, 'nama_kategori' => 'Pembayaran PO'],
                [
                    'deskripsi' => 'Pembayaran masuk dari PO (DP / pelunasan / ongkir / tambahan produk)',
                    'is_system'  => true,
                    'is_active'  => true,
                ]
            );

            Pemasukan::create([
                'brand_id'             => $order->brand_id,
                'kategori_pemasukan_id' => $kategori->id,
                'order_id'             => $order->id,
                'tanggal'              => $payment->payment_date,
                'nominal'              => $payment->amount,
                'keterangan'           => "{$label} PO {$order->no_po} — {$order->nama_po}",
                'is_auto'              => true,
                'created_by'           => $payment->recorded_by,
                'source_payment_id'    => $payment->id,
            ]);
        }
    }
}
