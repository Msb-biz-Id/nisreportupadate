<?php

namespace App\Models\Purchasing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PurchasingAssetMaintenance extends Model
{
    use HasFactory;

    protected $table = 'purchasing_asset_maintenances';

    protected $fillable = [
        'uuid',
        'asset_id',
        'maintenance_date',
        'service_type',
        'vendor_workshop',
        'issue_description',
        'action_taken',
        'cost',
        'status',
        'performed_by_user_id',
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

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date:Y-m-d',
            'cost' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(PurchasingAsset::class, 'asset_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
