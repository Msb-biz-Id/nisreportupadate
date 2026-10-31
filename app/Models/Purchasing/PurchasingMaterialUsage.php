<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PurchasingMaterialUsage extends Model
{
    use HasFactory;

    protected $table = 'purchasing_material_usages';

    protected $fillable = [
        'uuid',
        'material_stock_id',
        'usage_date',
        'material_type',
        'material_name',
        'unit',
        'quantity_in',
        'quantity_out',
        'balance_stock',
        'location_id',
        'department',
        'division',
        'operator_name',
        'production_ref',
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
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'uuid', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'quantity_in' => 'decimal:2',
            'quantity_out' => 'decimal:2',
            'balance_stock' => 'decimal:2',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(PurchasingMaterialStock::class, 'material_stock_id');
    }
}
