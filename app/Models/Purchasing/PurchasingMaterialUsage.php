<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $material_stock_id
 * @property \Illuminate\Support\Carbon|null $usage_date
 * @property string $material_type
 * @property string $material_name
 * @property string $unit
 * @property float $quantity_in
 * @property float $quantity_out
 * @property float $balance_stock
 * @property int|null $location_id
 * @property string|null $department
 * @property string|null $division
 * @property string|null $operator_name
 * @property string|null $production_ref
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Purchasing\PurchasingMaterialStock|null $stock
 */
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
