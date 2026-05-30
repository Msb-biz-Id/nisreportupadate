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
        // Automatically sequence DP
        if ($payment->payment_type === 'dp') {
            $lastDp = OrderPayment::where('order_id', $payment->order_id)
                ->where('payment_type', 'dp')
                ->max('dp_sequence');
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
        }

        if ($payment->verified_at !== null) {
            $this->recordLedger($payment);

            // Dispatch verified notification
            if ($order) {
                $formattedAmount = 'Rp ' . number_format($payment->amount, 0, ',', '.');
                \App\Services\Notifications\DynamicNotificationService::dispatch('payment_verified', [
                    'no_po' => $order->no_po,
                    'brand_id' => $order->brand_id,
                    'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
                    'nominal' => $formattedAmount,
                    'action_url' => route('orders.show', $order->id),
                ]);
            }
        } else {
            // Dispatch submitted notification
            if ($order) {
                $formattedAmount = 'Rp ' . number_format($payment->amount, 0, ',', '.');
                \App\Services\Notifications\DynamicNotificationService::dispatch('payment_submitted', [
                    'no_po' => $order->no_po,
                    'brand_id' => $order->brand_id,
                    'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
                    'nominal' => $formattedAmount,
                    'action_url' => route('orders.show', $order->id),
                ]);
            }
        }
    }

    public function updated(OrderPayment $payment): void
    {
        $order = $payment->order;
        if ($order) {
            $order->update(['total_tagihan' => $order->totalTagihan()]);
        }

        // If the payment just got verified (verified_at transitioned from null to a timestamp)
        if ($payment->isDirty('verified_at') && $payment->verified_at !== null && $payment->getOriginal('verified_at') === null) {
            $this->recordLedger($payment);

            // Dispatch verified notification
            if ($order) {
                $formattedAmount = 'Rp ' . number_format($payment->amount, 0, ',', '.');
                \App\Services\Notifications\DynamicNotificationService::dispatch('payment_verified', [
                    'no_po' => $order->no_po,
                    'brand_id' => $order->brand_id,
                    'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
                    'nominal' => $formattedAmount,
                    'action_url' => route('orders.show', $order->id),
                ]);
            }
        }
    }

    public function deleted(OrderPayment $payment): void
    {
        $order = $payment->order;
        if ($order) {
            $order->update(['total_tagihan' => $order->totalTagihan()]);
        }
    }

    private function recordLedger(OrderPayment $payment): void
    {
        $order = $payment->order;
        if (! $order) return;

        if (! $payment->is_debit) {
            // Record as Pengeluaran
            if (Pengeluaran::where('brand_id', $order->brand_id)->where('nominal', $payment->amount)->where('tanggal', $payment->payment_date)->where('keterangan', 'like', "%{$order->no_po}%")->exists()) {
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
            ]);
        } else {
            // Record as Pemasukan
            if (Pemasukan::where('order_id', $order->id)->where('nominal', $payment->amount)->where('tanggal', $payment->payment_date)->where('keterangan', 'like', "%PO {$order->no_po}%")->exists()) {
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
            ]);
        }
    }
}
