<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchasingVendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchasing_vendors';

    protected $fillable = [
        'uuid',
        'vendor_code',
        'name',
        'category',
        'item_category',
        'item_name',
        'specification',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_account_holder',
        'default_top_days',
        'is_active',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->vendor_code)) {
                $count = static::withTrashed()->count() + 1;
                $model->vendor_code = 'VND-' . str_pad((string)$count, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'uuid', $value)
            ->orWhere('vendor_code', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'default_top_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PurchasingVendorContact::class, 'vendor_id')->orderByDesc('is_primary');
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(PurchasingVendorContact::class, 'vendor_id')->where('is_primary', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PurchasingOrder::class, 'vendor_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(PurchasingAsset::class, 'vendor_id');
    }
}
