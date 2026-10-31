<?php

namespace App\Services\Purchasing;

use App\Models\Hcm\HcmEmployee;
use App\Models\Purchasing\PurchasingOrder;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchasingOrderService
{
    /**
     * Generate unique PO Number: PO-YYYYMM-XXXX
     * Anti-race condition with database lock
     */
    public function generatePoNumber(?string $date = null): string
    {
        $carbonDate = $date ? Carbon::parse($date) : Carbon::now();
        $prefix = 'PO-' . $carbonDate->format('Ym') . '-';

        return DB::transaction(function () use ($prefix) {
            $latestPo = PurchasingOrder::withTrashed()
                ->where('po_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('po_number')
                ->value('po_number');

            $nextSequence = 1;
            if ($latestPo) {
                $lastSeqString = substr($latestPo, strlen($prefix));
                if (is_numeric($lastSeqString)) {
                    $nextSequence = (int) $lastSeqString + 1;
                }
            }

            return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Reusable mathematical total calculations
     */
    public function calculateTotals(
        float $quantity,
        float $unitPrice,
        float $discountAmount = 0.0,
        float $shippingCost = 0.0,
        float $taxAmount = 0.0
    ): array {
        $subtotal = round($quantity * $unitPrice, 2);
        $discount = round(max(0, $discountAmount), 2);
        $shipping = round(max(0, $shippingCost), 2);
        $tax = round(max(0, $taxAmount), 2);
        $grandTotal = round(max(0, $subtotal - $discount + $shipping + $tax), 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'shipping_cost' => $shipping,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Create new Purchasing Order with auto calculations & snapshotting
     */
    public function createOrder(array $data, ?UploadedFile $invoiceFile = null, User $user): PurchasingOrder
    {
        return DB::transaction(function () use ($data, $invoiceFile, $user) {
            $transactionDate = $data['transaction_date'] ?? Carbon::now()->toDateString();
            $poNumber = $this->generatePoNumber($transactionDate);

            // Snapshot data pemohon dari HRIS jika ada ID Karyawan
            $requesterId = $data['requester_employee_id'] ?? null;
            $requesterName = $data['requester_name'] ?? null;
            $department = $data['department'] ?? '-';
            $division = $data['division'] ?? null;
            $position = $data['position'] ?? '-';

            if ($requesterId) {
                $employee = HcmEmployee::find($requesterId);
                if ($employee) {
                    $requesterName = $employee->full_name ?? $employee->name ?? $requesterName;
                    $department = $employee->department ?? $department;
                    $division = $employee->division ?? $division;
                    $position = $employee->position ?? $position;
                }
            }

            // Hitung kalkulasi angka otomatis
            $totals = $this->calculateTotals(
                (float) ($data['quantity'] ?? 1),
                (float) ($data['unit_price'] ?? 0),
                (float) ($data['discount_amount'] ?? 0),
                (float) ($data['shipping_cost'] ?? 0),
                (float) ($data['tax_amount'] ?? 0)
            );

            // Handle upload berkas nota / invoice jika ada
            $invoiceFilePath = null;
            if ($invoiceFile && $invoiceFile->isValid()) {
                $invoiceFilePath = $this->storeInvoiceFile($invoiceFile, $poNumber);
            }

            $order = PurchasingOrder::create([
                'po_number' => $poNumber,
                'transaction_date' => $transactionDate,
                'order_type' => $data['order_type'] ?? 'OPEX',
                'requester_employee_id' => $requesterId,
                'requester_name' => $requesterName,
                'department' => $department,
                'division' => $division,
                'position' => $position,
                'location_id' => $data['location_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'vendor_name_manual' => $data['vendor_name_manual'] ?? null,
                'item_category_id' => $data['item_category_id'] ?? null,
                'item_name' => $data['item_name'],
                'specification' => $data['specification'] ?? null,
                'unit' => $data['unit'] ?? 'Pcs',
                'quantity' => $data['quantity'] ?? 1,
                'unit_price' => $data['unit_price'] ?? 0,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'shipping_cost' => $totals['shipping_cost'],
                'tax_amount' => $totals['tax_amount'],
                'grand_total' => $totals['grand_total'],
                'payment_type' => $data['payment_type'] ?? 'Tunai / Cash',
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_file_path' => $invoiceFilePath,
                'status' => $data['status'] ?? 'PENDING_PIC_CHECK',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            ActivityLogger::log('create', 'purchasing', $order, "Membuat PO baru {$order->po_number}: {$order->item_name} sebesar Rp " . number_format($order->grand_total, 0, ',', '.'));

            return $order;
        });
    }

    /**
     * Update existing Purchasing Order
     */
    public function updateOrder(PurchasingOrder $order, array $data, ?UploadedFile $invoiceFile = null, User $user): PurchasingOrder
    {
        return DB::transaction(function () use ($order, $data, $invoiceFile, $user) {
            // Snapshot data pemohon dari HRIS jika diperbarui
            $requesterId = array_key_exists('requester_employee_id', $data) ? $data['requester_employee_id'] : $order->requester_employee_id;
            $requesterName = $data['requester_name'] ?? $order->requester_name;
            $department = $data['department'] ?? $order->department;
            $division = array_key_exists('division', $data) ? $data['division'] : $order->division;
            $position = $data['position'] ?? $order->position;

            if ($requesterId && $requesterId !== $order->requester_employee_id) {
                $employee = HcmEmployee::find($requesterId);
                if ($employee) {
                    $requesterName = $employee->full_name ?? $employee->name ?? $requesterName;
                    $department = $employee->department ?? $department;
                    $division = $employee->division ?? $division;
                    $position = $employee->position ?? $position;
                }
            }

            // Hitung ulang total matematika
            $quantity = (float) ($data['quantity'] ?? $order->quantity);
            $unitPrice = (float) ($data['unit_price'] ?? $order->unit_price);
            $discountAmount = (float) ($data['discount_amount'] ?? $order->discount_amount);
            $shippingCost = (float) ($data['shipping_cost'] ?? $order->shipping_cost);
            $taxAmount = (float) ($data['tax_amount'] ?? $order->tax_amount);

            $totals = $this->calculateTotals($quantity, $unitPrice, $discountAmount, $shippingCost, $taxAmount);

            // Handle update invoice file jika diunggah berkas baru
            $invoiceFilePath = $order->invoice_file_path;
            if ($invoiceFile && $invoiceFile->isValid()) {
                if ($order->invoice_file_path && Storage::disk('public')->exists($order->invoice_file_path)) {
                    Storage::disk('public')->delete($order->invoice_file_path);
                }
                $invoiceFilePath = $this->storeInvoiceFile($invoiceFile, $order->po_number);
            }

            $order->update([
                'transaction_date' => $data['transaction_date'] ?? $order->transaction_date,
                'order_type' => $data['order_type'] ?? $order->order_type,
                'requester_employee_id' => $requesterId,
                'requester_name' => $requesterName,
                'department' => $department,
                'division' => $division,
                'position' => $position,
                'location_id' => array_key_exists('location_id', $data) ? $data['location_id'] : $order->location_id,
                'vendor_id' => array_key_exists('vendor_id', $data) ? $data['vendor_id'] : $order->vendor_id,
                'vendor_name_manual' => array_key_exists('vendor_name_manual', $data) ? $data['vendor_name_manual'] : $order->vendor_name_manual,
                'item_category_id' => array_key_exists('item_category_id', $data) ? $data['item_category_id'] : $order->item_category_id,
                'item_name' => $data['item_name'] ?? $order->item_name,
                'specification' => array_key_exists('specification', $data) ? $data['specification'] : $order->specification,
                'unit' => $data['unit'] ?? $order->unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'shipping_cost' => $totals['shipping_cost'],
                'tax_amount' => $totals['tax_amount'],
                'grand_total' => $totals['grand_total'],
                'payment_type' => $data['payment_type'] ?? $order->payment_type,
                'invoice_number' => array_key_exists('invoice_number', $data) ? $data['invoice_number'] : $order->invoice_number,
                'invoice_file_path' => $invoiceFilePath,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $order->notes,
            ]);

            ActivityLogger::log('update', 'purchasing', $order, "Memperbarui PO {$order->po_number}: {$order->item_name}");

            return $order;
        });
    }

    /**
     * Approve PO by PIC Operasional Purchasing
     */
    public function approvePic(PurchasingOrder $order, User $user, ?string $notes = null): PurchasingOrder
    {
        return DB::transaction(function () use ($order, $user, $notes) {
            $updatedNotes = $order->notes;
            if ($notes) {
                $updatedNotes = ($updatedNotes ? $updatedNotes . "\n" : '') . "[PIC Check: {$notes}]";
            }

            $order->update([
                'status' => 'APPROVED_BY_PIC',
                'pic_approved_by' => $user->id,
                'pic_approved_at' => Carbon::now(),
                'notes' => $updatedNotes,
            ]);

            ActivityLogger::log('approve', 'purchasing', $order, "PIC Purchasing ({$user->name}) menyetujui verifikasi teknis PO {$order->po_number}");

            return $order;
        });
    }

    /**
     * Approve Budget & Sign-Off by Finance (admin_keuangan)
     */
    public function approveFinance(PurchasingOrder $order, User $user, ?string $notes = null): PurchasingOrder
    {
        return DB::transaction(function () use ($order, $user, $notes) {
            $updatedNotes = $order->notes;
            if ($notes) {
                $updatedNotes = ($updatedNotes ? $updatedNotes . "\n" : '') . "[Finance Sign-Off: {$notes}]";
            }

            $order->update([
                'status' => 'PURCHASE_COMPLETED',
                'finance_approved_by' => $user->id,
                'finance_approved_at' => Carbon::now(),
                'notes' => $updatedNotes,
            ]);

            ActivityLogger::log('approve', 'purchasing', $order, "Keuangan ({$user->name}) menyetujui otorisasi anggaran (Double Sign-Off) PO {$order->po_number}");

            return $order;
        });
    }

    /**
     * Reject PO with mandatory reason
     */
    public function rejectOrder(PurchasingOrder $order, User $user, string $reason): PurchasingOrder
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $updatedNotes = ($order->notes ? $order->notes . "\n" : '') . "[DITOLAK oleh {$user->name}: {$reason}]";

            $order->update([
                'status' => 'REJECTED',
                'notes' => $updatedNotes,
            ]);

            ActivityLogger::log('reject', 'purchasing', $order, "PO {$order->po_number} ditolak oleh {$user->name}. Alasan: {$reason}");

            return $order;
        });
    }

    /**
     * Live search purchase history by item keywords, specs, or PO number
     * Returns historical purchase benchmark data
     */
    public function searchPurchaseHistory(string $keyword, int $limit = 10): Collection
    {
        $cleanKeyword = trim($keyword);
        if (empty($cleanKeyword)) {
            return collect([]);
        }

        return PurchasingOrder::query()
            ->with(['vendor.contacts', 'vendor.primaryContact', 'location', 'itemCategory'])
            ->where(function ($q) use ($cleanKeyword) {
                $q->where('item_name', 'like', "%{$cleanKeyword}%")
                    ->orWhere('specification', 'like', "%{$cleanKeyword}%")
                    ->orWhere('po_number', 'like', "%{$cleanKeyword}%")
                    ->orWhere('vendor_name_manual', 'like', "%{$cleanKeyword}%")
                    ->orWhereHas('vendor', function ($vq) use ($cleanKeyword) {
                        $vq->where('name', 'like', "%{$cleanKeyword}%");
                    });
            })
            ->whereIn('status', ['APPROVED_BY_PIC', 'PURCHASE_COMPLETED', 'PAID_COMPLETED'])
            ->orderByDesc('transaction_date')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $vendorName = $order->vendor?->name ?? $order->vendor_name_manual ?? 'Vendor Umum';
                $vendorPhone = $order->vendor?->primaryContact?->phone
                    ?? $order->vendor?->contacts?->first()?->phone
                    ?? null;

                return [
                    'id' => $order->id,
                    'uuid' => $order->uuid,
                    'po_number' => $order->po_number,
                    'transaction_date' => $order->transaction_date?->format('Y-m-d'),
                    'item_name' => $order->item_name,
                    'specification' => $order->specification,
                    'unit' => $order->unit,
                    'quantity' => (float) $order->quantity,
                    'unit_price' => (float) $order->unit_price,
                    'grand_total' => (float) $order->grand_total,
                    'department' => $order->department,
                    'vendor_id' => $order->vendor_id,
                    'vendor_name' => $vendorName,
                    'vendor_phone' => $vendorPhone,
                    'payment_type' => $order->payment_type,
                    'location_name' => $order->location?->name,
                    'status' => $order->status,
                ];
            });
    }

    /**
     * Store uploaded invoice file safely
     */
    protected function storeInvoiceFile(UploadedFile $file, string $poNumber): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $sanitizedPo = Str::slug($poNumber);
        $fileName = "invoice_{$sanitizedPo}_" . time() . '_' . Str::random(8) . '.' . $ext;

        return $file->storeAs('purchasing/invoices', $fileName, 'public');
    }
}
