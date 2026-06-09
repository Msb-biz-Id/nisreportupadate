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
        $cleanName = (string) str($namaPo)->slug('-')->upper();
        $cleanName = substr($cleanName, 0, 30);
        $cleanName = rtrim($cleanName, '-');
        if (empty($cleanName)) {
            $cleanName = 'ORDER';
        }

        $prefix = "PO-{$brand->kode}-{$cleanName}";

        // Find the last sequence globally for this brand
        $lastOrders = Order::where('brand_id', $brand->id)
            ->withTrashed()
            ->whereNotNull('no_po')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $seq = 1;
        foreach ($lastOrders as $order) {
            $parts = explode('-', $order->no_po);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $seq = ((int) $lastPart) + 1;
                break;
            }
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

        $prefix = "INV-{$brand->kode}-ORDER";

        // Find the last sequence globally for this brand's invoices
        $lastInvoices = Invoice::where('brand_id', $brand->id)
            ->withTrashed()
            ->whereNotNull('invoice_number')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $seq = 1;
        foreach ($lastInvoices as $inv) {
            $parts = explode('-', $inv->invoice_number);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $seq = ((int) $lastPart) + 1;
                break;
            }
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
