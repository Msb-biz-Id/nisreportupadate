<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $vendor_id
 * @property string $pic_name
 * @property string|null $role_title
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_primary
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Purchasing\PurchasingVendor|null $vendor
 */
class PurchasingVendorContact extends Model
{
    use HasFactory;

    protected $table = 'purchasing_vendor_contacts';

    protected $fillable = [
        'uuid',
        'vendor_id',
        'pic_name',
        'role_title',
        'phone',
        'email',
        'is_primary',
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
            'vendor_id' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(PurchasingVendor::class, 'vendor_id');
    }
}
