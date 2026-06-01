<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\Refund;
use Carbon\Carbon;

class NumberGenerator
{
    public function generateOrderNumber(Brand $brand, string $namaPo = ''): string
    {
        $year = Carbon::now()->year;
        $prefix = "PO-{$brand->kode}-{$year}";

        $last = Order::where('brand_id', $brand->id)
            ->where('no_po', 'like', "{$prefix}-%")
            ->withTrashed()
            ->orderByDesc('no_po')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->no_po);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function generateInvoiceNumber(Brand $brand, ?Order $order = null): string
    {
        if ($order && $order->no_po) {
            if (str_starts_with($order->no_po, 'PO-')) {
                return 'INV-' . substr($order->no_po, 3);
            }
            return 'INV-' . $order->no_po;
        }

        $date = Carbon::now()->format('Ymd');
        $prefix = "INV-{$brand->kode}-{$date}";

        $last = Invoice::where('brand_id', $brand->id)
            ->where('invoice_number', 'like', "{$prefix}-%")
            ->withTrashed()
            ->orderByDesc('invoice_number')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->invoice_number);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function generateRefundNumber(Brand $brand): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "REF-{$brand->kode}-{$date}";

        $last = Refund::where('brand_id', $brand->id)
            ->where('refund_number', 'like', "{$prefix}-%")
            ->withTrashed()
            ->orderByDesc('refund_number')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->refund_number);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function generateDepositNumber(Brand $brand): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "TJ-{$brand->kode}-{$date}";

        $last = \App\Models\Order\DesignDeposit::where('brand_id', $brand->id)
            ->where('deposit_number', 'like', "{$prefix}-%")
            ->withTrashed()
            ->orderByDesc('deposit_number')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->deposit_number);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
