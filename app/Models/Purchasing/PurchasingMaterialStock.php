<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchasingMaterialStock extends Model
{
    use HasFactory;

    protected $table = 'purchasing_material_stocks';

    protected $fillable = [
        'uuid',
        'material_type',
        'code',
        'name',
        'unit',
        'minimum_threshold',
        'current_stock',
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
        return 'code';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'code', $value)
            ->orWhere('uuid', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'minimum_threshold' => 'decimal:2',
            'current_stock' => 'decimal:2',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PurchasingMaterialUsage::class, 'material_stock_id')->orderByDesc('usage_date');
    }
}
