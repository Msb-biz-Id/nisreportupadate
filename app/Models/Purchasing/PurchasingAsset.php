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
}
