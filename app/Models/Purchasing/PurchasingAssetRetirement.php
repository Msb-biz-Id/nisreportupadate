<?php

namespace App\Models\Purchasing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $asset_id
 * @property \Illuminate\Support\Carbon|null $retired_at
 * @property string|null $department
 * @property string|null $division
 * @property string|null $position
 * @property int|null $location_id
 * @property string $retirement_reason
 * @property string|null $final_condition
 * @property string|null $disposal_method
 * @property float|null $book_value
 * @property float|null $disposal_price
 * @property int|null $finance_approved_by
 * @property \Illuminate\Support\Carbon|null $finance_approved_at
 * @property string|null $handover_document_path
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Purchasing\PurchasingAsset|null $asset
 * @property-read \App\Models\User|null $financeApprover
 * @property-read \App\Models\User|null $creator
 */
class PurchasingAssetRetirement extends Model
{
    use HasFactory;

    protected $table = 'purchasing_asset_retirements';

    protected $fillable = [
        'uuid',
        'asset_id',
        'retired_at',
        'department',
        'division',
        'position',
        'location_id',
        'retirement_reason',
        'final_condition',
        'disposal_method',
        'book_value',
        'disposal_price',
        'finance_approved_by',
        'finance_approved_at',
        'handover_document_path',
        'notes',
        'created_by',
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
            'retired_at' => 'date:Y-m-d',
            'book_value' => 'decimal:2',
            'disposal_price' => 'decimal:2',
            'finance_approved_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(PurchasingAsset::class, 'asset_id');
    }

    public function financeApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
