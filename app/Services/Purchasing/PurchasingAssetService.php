<?php

namespace App\Services\Purchasing;

use App\Models\Hcm\HcmEmployee;
use App\Models\Purchasing\PurchasingAsset;
use App\Models\Purchasing\PurchasingAssetMutation;
use App\Models\Purchasing\PurchasingAssetRetirement;
use App\Models\Purchasing\PurchasingAssetSuggestion;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchasingAssetService
{
    public function __construct(
        protected PurchasingAssetCodeService $codeService
    ) {}

    /**
     * Registrasi Aset Tahap 1: Quick Registration.
     */
    public function quickRegister(array $data, ?User $user = null): PurchasingAsset
    {
        return DB::transaction(function () use ($data, $user) {
            $purchaseDate = !empty($data['purchase_date']) ? Carbon::parse($data['purchase_date']) : Carbon::today();
            $deptName = $data['department'] ?? 'Umum';
            $deptCode = $data['department_code'] ?? $this->codeService->resolveDepartmentCode($deptName);
            $catCode = $this->codeService->sanitizeCategoryCode($data['category_code']);

            $codeResult = $this->codeService->generateAssetCode(
                $catCode,
                $deptCode,
                (int) $purchaseDate->format('Y')
            );

            $picName = null;
            if (!empty($data['pic_employee_id'])) {
                $employee = HcmEmployee::find($data['pic_employee_id']);
                $picName = $employee?->name;
            } elseif (!empty($data['pic_employee_name'])) {
                $picName = $data['pic_employee_name'];
            }

            // Ambil ID kategori aset jika ada di purchasing_master_options
            $categoryOption = PurchasingMasterOption::where('category', 'asset_category')
                ->where('code', $catCode)
                ->first();

            $asset = PurchasingAsset::create([
                'asset_code' => $codeResult['asset_code'],
                'category_code' => $codeResult['category_code'],
                'department_code' => $codeResult['department_code'],
                'year_code' => $codeResult['year_code'],
                'sequence_number' => $codeResult['sequence_number'],
                'registration_stage' => 'QUICK_REGISTERED',
                'order_id' => $data['order_id'] ?? null,
                'pic_employee_id' => $data['pic_employee_id'] ?? null,
                'pic_employee_name' => $picName,
                'asset_name' => $data['asset_name'],
                'asset_category_id' => $categoryOption?->id ?? ($data['asset_category_id'] ?? null),
                'specification' => $data['specification'] ?? null,
                'unit' => $data['unit'] ?? 'Unit',
                'quantity' => (int) ($data['quantity'] ?? 1),
                'location_id' => $data['location_id'] ?? null,
                'department' => $deptName,
                'division' => $data['division'] ?? null,
                'position' => $data['position'] ?? 'Umum',
                'vendor_id' => $data['vendor_id'] ?? null,
                'supplier_name' => $data['supplier_name'] ?? null,
                'purchase_date' => $purchaseDate->toDateString(),
                'received_date' => $data['received_date'] ?? $purchaseDate->toDateString(),
                'acquisition_cost' => (float) ($data['acquisition_cost'] ?? 0.00),
                'warranty_duration' => $data['warranty_duration'] ?? null,
                'warranty_expires_at' => $data['warranty_expires_at'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'barcode_qr_code' => $data['barcode_qr_code'] ?? $codeResult['asset_code'],
                'status' => 'ACTIVE',
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogger::log(
                'create',
                'purchasing',
                $asset,
                "Registrasi cepat aset {$asset->asset_code}: {$asset->asset_name} ({$asset->department})"
            );

            return $asset;
        });
    }

    /**
     * Registrasi Aset Tahap 2: Deep Registration (Lengkapi spesifikasi fisik, serial number, garansi, dll).
     */
    public function completeRegistration(PurchasingAsset $asset, array $data, ?User $user = null): PurchasingAsset
    {
        return DB::transaction(function () use ($asset, $data) {
            $updates = [
                'registration_stage' => 'COMPLETED',
            ];

            if (array_key_exists('specification', $data)) {
                $updates['specification'] = $data['specification'];
            }
            if (array_key_exists('serial_number', $data)) {
                $updates['serial_number'] = $data['serial_number'];
            }
            if (array_key_exists('barcode_qr_code', $data)) {
                $updates['barcode_qr_code'] = $data['barcode_qr_code'];
            }
            if (array_key_exists('warranty_duration', $data)) {
                $updates['warranty_duration'] = $data['warranty_duration'];
            }
            if (array_key_exists('warranty_expires_at', $data)) {
                $updates['warranty_expires_at'] = $data['warranty_expires_at'];
            }
            if (array_key_exists('location_id', $data)) {
                $updates['location_id'] = $data['location_id'];
            }
            if (array_key_exists('unit', $data)) {
                $updates['unit'] = $data['unit'];
            }
            if (array_key_exists('notes', $data)) {
                $updates['notes'] = $data['notes'];
            }
            if (!empty($data['pic_employee_id'])) {
                $updates['pic_employee_id'] = $data['pic_employee_id'];
                $emp = HcmEmployee::find($data['pic_employee_id']);
                $updates['pic_employee_name'] = $emp?->name;
            }

            $asset->update($updates);

            ActivityLogger::log(
                'update',
                'purchasing',
                $asset,
                "Melengkapi data fisik aset {$asset->asset_code}: {$asset->asset_name}"
            );

            return $asset->fresh();
        });
    }

    /**
     * Mutasi Aset Antar-Departemen (Regenerasi kode resmi & catat riwayat permanen).
     */
    public function mutateAsset(PurchasingAsset $asset, array $data, User $user): PurchasingAssetMutation
    {
        if ($asset->status === 'RETIRED') {
            throw new InvalidArgumentException("Aset yang telah berstatus RETIRED tidak dapat dimutasi.");
        }

        return DB::transaction(function () use ($asset, $data, $user) {
            $toDepartmentName = $data['to_department'];
            $toDepartmentCode = $this->codeService->resolveDepartmentCode($toDepartmentName);

            // Generate kode aset baru
            $codeResult = $this->codeService->generateMutatedAssetCode($asset, $toDepartmentCode);

            // Simpan riwayat mutasi
            $mutation = PurchasingAssetMutation::create([
                'asset_id' => $asset->id,
                'old_asset_code' => $asset->asset_code,
                'new_asset_code' => $codeResult['asset_code'],
                'from_department_code' => $asset->department_code,
                'to_department_code' => $codeResult['department_code'],
                'from_department_name' => $asset->department,
                'to_department_name' => $toDepartmentName,
                'mutation_date' => $data['mutation_date'] ?? Carbon::today()->toDateString(),
                'pic_user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Siapkan update untuk aset
            $assetUpdates = [
                'asset_code' => $codeResult['asset_code'],
                'department_code' => $codeResult['department_code'],
                'sequence_number' => $codeResult['sequence_number'],
                'department' => $toDepartmentName,
                'division' => $data['to_division'] ?? $asset->division,
                'position' => $data['to_position'] ?? $asset->position,
                'status' => 'ACTIVE',
            ];

            if (!empty($data['to_pic_employee_id'])) {
                $newPic = HcmEmployee::find($data['to_pic_employee_id']);
                $assetUpdates['pic_employee_id'] = $newPic?->id;
                $assetUpdates['pic_employee_name'] = $newPic?->name;
            }

            if (!empty($data['to_location_id'])) {
                $assetUpdates['location_id'] = $data['to_location_id'];
            }

            $asset->update($assetUpdates);

            ActivityLogger::log(
                'asset-mutate',
                'purchasing',
                $asset,
                "Mutasi aset dari {$mutation->from_department_name} ke {$mutation->to_department_name} (Kode baru: {$asset->asset_code})"
            );

            return $mutation;
        });
    }

    /**
     * Asset Retirement (Pelepasan/Pensiun Aset, Dampak Nilai Buku & Approval).
     */
    public function retireAsset(PurchasingAsset $asset, array $data, User $user): PurchasingAssetRetirement
    {
        if ($asset->status === 'RETIRED') {
            throw new InvalidArgumentException("Aset {$asset->asset_code} sudah berstatus RETIRED.");
        }

        return DB::transaction(function () use ($asset, $data, $user) {
            $isFinanceOrSuper = $user->hasRole(['admin_keuangan', 'superadmin']);

            $retirement = PurchasingAssetRetirement::create([
                'asset_id' => $asset->id,
                'retired_at' => $data['retired_at'] ?? Carbon::today()->toDateString(),
                'department' => $asset->department,
                'division' => $asset->division,
                'position' => $asset->position,
                'location_id' => $data['location_id'] ?? $asset->location_id,
                'retirement_reason' => $data['retirement_reason'],
                'final_condition' => $data['final_condition'],
                'disposal_method' => $data['disposal_method'],
                'book_value' => (float) ($data['book_value'] ?? 0.00),
                'disposal_price' => isset($data['disposal_price']) ? (float) $data['disposal_price'] : null,
                'finance_approved_by' => $isFinanceOrSuper ? $user->id : null,
                'finance_approved_at' => $isFinanceOrSuper ? Carbon::now() : null,
                'handover_document_path' => $data['handover_document_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $asset->update([
                'status' => 'RETIRED',
            ]);

            ActivityLogger::log(
                'retire',
                'purchasing',
                $asset,
                "Pelepasan aset {$asset->asset_code} ({$asset->asset_name}) - Metode: {$retirement->disposal_method}"
            );

            return $retirement;
        });
    }

    /**
     * Smart Suggestion Engine: Pencocokan keyword nama barang ke kategori aset resmi.
     *
     * @return array<int, array{category_code: string, category_name: string}>
     */
    public function suggestCategories(string $keyword): array
    {
        $clean = trim($keyword);
        if (empty($clean)) {
            return [];
        }

        // 1. Coba pencocokan kata kunci di purchasing_asset_suggestions
        $suggestions = PurchasingAssetSuggestion::where('keyword', 'LIKE', "%{$clean}%")
            ->limit(5)
            ->get(['category_code', 'category_name']);

        if ($suggestions->isNotEmpty()) {
            return $suggestions->toArray();
        }

        // 2. Fallback pencocokan kata di master option asset_category
        $masterCategories = PurchasingMasterOption::where('category', 'asset_category')
            ->where('is_active', true)
            ->where(function ($q) use ($clean) {
                $q->where('name', 'LIKE', "%{$clean}%")
                  ->orWhere('code', 'LIKE', "%{$clean}%");
            })
            ->limit(5)
            ->get(['code as category_code', 'name as category_name']);

        return $masterCategories->toArray();
    }
}
