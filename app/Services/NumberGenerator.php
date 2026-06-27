<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\Refund;
use Carbon\Carbon;

class NumberGenerator
{
    public function generateOrderNumber(Brand $brand, string $namaPo = '', ?Carbon $date = null): string
    {
        $cleanName = (string) str($namaPo)->slug('-')->upper();
        $cleanName = substr($cleanName, 0, 30);
        $cleanName = rtrim($cleanName, '-');
        if (empty($cleanName)) {
            $cleanName = 'ORDER';
        }

        $prefix = "PO-{$brand->kode}-{$cleanName}";

        $date = $date ?? Carbon::now();
        $year = $date->year;

        // Find the last sequence globally for this brand in the same year of tanggal_masuk
        $lastOrderNumbers = Order::where('brand_id', $brand->id)
            ->whereBetween('tanggal_masuk', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
            ->withTrashed()
            ->whereNotNull('no_po')
            ->orderByDesc('id')
            ->limit(50)
            ->pluck('no_po');

        $seq = 1;
        foreach ($lastOrderNumbers as $noPo) {
            $parts = explode('-', $noPo);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $seq = ((int) $lastPart) + 1;
                break;
            }
        }

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function generateInvoiceNumber(Brand $brand, ?Order $order = null, ?Carbon $date = null): string
    {
        if ($order && $order->no_po) {
            if (str_starts_with($order->no_po, 'PO-')) {
                return 'INV-' . substr($order->no_po, 3);
            }
            return 'INV-' . $order->no_po;
        }

        $date = $date ?? Carbon::now();
        $year = $date->year;
        $prefix = "INV-{$brand->kode}-ORDER";

        // Find the last sequence globally for this brand's invoices in the same year
        $lastInvoiceNumbers = Invoice::where('brand_id', $brand->id)
            ->where(function ($q) use ($year) {
                $q->whereBetween('tanggal_terbit', ["{$year}-01-01", "{$year}-12-31"])
                  ->orWhereBetween('created_at', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"]);
            })
            ->withTrashed()
            ->whereNotNull('invoice_number')
            ->orderByDesc('id')
            ->limit(50)
            ->pluck('invoice_number');

        $seq = 1;
        foreach ($lastInvoiceNumbers as $invoiceNumber) {
            $parts = explode('-', $invoiceNumber);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $seq = ((int) $lastPart) + 1;
                break;
            }
        }

        return $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function generateRefundNumber(Brand $brand, ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $dateStr = $date->format('Ymd');
        $prefix = "REF-{$brand->kode}-{$dateStr}";

        $lastRefundNumber = Refund::where('brand_id', $brand->id)
            ->where('refund_number', 'like', "{$prefix}-%")
            ->withTrashed()
            ->orderByDesc('refund_number')
            ->value('refund_number');

        $seq = 1;
        if ($lastRefundNumber) {
            $parts = explode('-', $lastRefundNumber);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function generateDepositNumber(Brand $brand, ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $dateStr = $date->format('Ymd');
        $prefix = "TJ-{$brand->kode}-{$dateStr}";

        $lastDepositNumber = \App\Models\Order\DesignDeposit::where('brand_id', $brand->id)
            ->where('deposit_number', 'like', "{$prefix}-%")
            ->withTrashed()
            ->orderByDesc('deposit_number')
            ->value('deposit_number');

        $seq = 1;
        if ($lastDepositNumber) {
            $parts = explode('-', $lastDepositNumber);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
