<?php

namespace App\Models\Purchasing;

use App\Models\Hcm\HcmEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $asset_code
 * @property string|null $category_code
 * @property string|null $department_code
 * @property string|null $year_code
 * @property int|null $sequence_number
 * @property string $registration_stage
 * @property int|null $order_id
 * @property int|null $pic_employee_id
 * @property string|null $pic_employee_name
 * @property string $asset_name
 * @property int|null $asset_category_id
 * @property string|null $specification
 * @property string|null $unit
 * @property int $quantity
 * @property int|null $location_id
 * @property string|null $department
 * @property string|null $division
 * @property string|null $position
 * @property int|null $vendor_id
 * @property string|null $supplier_name
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property \Illuminate\Support\Carbon|null $received_date
 * @property float|null $acquisition_cost
 * @property string|null $warranty_duration
 * @property \Illuminate\Support\Carbon|null $warranty_expires_at
 * @property string|null $serial_number
 * @property string|null $barcode_qr_code
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Purchasing\PurchasingOrder|null $order
 * @property-read \App\Models\Purchasing\PurchasingVendor|null $vendor
 * @property-read \App\Models\Hcm\HcmEmployee|null $picEmployee
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Purchasing\PurchasingAssetMutation[] $mutations
 * @property-read \App\Models\Purchasing\PurchasingAssetRetirement|null $retirement
 */
class PurchasingAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchasing_assets';

    protected $fillable = [
        'uuid',
        'asset_code',
        'category_code',
        'department_code',
        'year_code',
        'sequence_number',
        'registration_stage',
        'order_id',
        'pic_employee_id',
        'pic_employee_name',
        'asset_name',
        'asset_category_id',
        'specification',
        'unit',
        'quantity',
        'location_id',
        'department',
        'division',
        'position',
        'vendor_id',
        'supplier_name',
        'purchase_date',
        'received_date',
        'acquisition_cost',
        'warranty_duration',
        'warranty_expires_at',
        'serial_number',
        'barcode_qr_code',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'asset_code';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'asset_code', $value)
            ->orWhere('uuid', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'quantity' => 'integer',
            'purchase_date' => 'date',
            'received_date' => 'date',
            'warranty_expires_at' => 'date',
            'acquisition_cost' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchasingOrder::class, 'order_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(PurchasingVendor::class, 'vendor_id');
    }

    public function picEmployee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'pic_employee_id');
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(PurchasingAssetMutation::class, 'asset_id')->orderByDesc('mutation_date');
    }

    public function retirement(): HasOne
    {
        return $this->hasOne(PurchasingAssetRetirement::class, 'asset_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(PurchasingAssetMaintenance::class, 'asset_id')->orderByDesc('maintenance_date');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PurchasingMasterOption::class, 'location_id');
    }
}
