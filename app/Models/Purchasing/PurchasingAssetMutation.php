<?php

namespace App\Models\Purchasing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PurchasingAssetMutation extends Model
{
    use HasFactory;

    protected $table = 'purchasing_asset_mutations';

    protected $fillable = [
        'uuid',
        'asset_id',
        'old_asset_code',
        'new_asset_code',
        'from_department_code',
        'to_department_code',
        'from_department_name',
        'to_department_name',
        'mutation_date',
        'pic_user_id',
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
            'mutation_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(PurchasingAsset::class, 'asset_id');
    }

    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }
}
