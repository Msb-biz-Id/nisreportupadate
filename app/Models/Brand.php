<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const TYPE_REGULAR = 'regular';
    public const TYPE_RESELLER_HUB = 'reseller_hub';
    public const TYPE_RESELLER_BRANCH = 'reseller_branch';

    protected $fillable = [
        'nama_brand',
        'kode',
        'tagline',
        'deskripsi',
        'logo',
        'favicon',
        'email',
        'no_hp',
        'alamat',
        'instagram',
        'facebook',
        'tiktok',
        'whatsapp',
        'website',
        'timezone',
        'currency',
        'warna_primary',
        'is_active',
        'brand_type',
        'parent_brand_id',
        'min_dp_percentage',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_dp_percentage' => 'float',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_brand_access')
            ->withPivot(['is_default', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function brandAccess(): HasMany
    {
        return $this->hasMany(UserBrandAccess::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentBrand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'parent_brand_id');
    }

    public function childBrands(): HasMany
    {
        return $this->hasMany(Brand::class, 'parent_brand_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(\App\Models\Master\BankAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('brand_type', self::TYPE_REGULAR);
    }

    public function scopeResellerHub($query)
    {
        return $query->where('brand_type', self::TYPE_RESELLER_HUB);
    }

    public function scopeResellerBranch($query)
    {
        return $query->where('brand_type', self::TYPE_RESELLER_BRANCH);
    }

    public function isResellerHub(): bool
    {
        return $this->brand_type === self::TYPE_RESELLER_HUB;
    }

    public function isResellerBranch(): bool
    {
        return $this->brand_type === self::TYPE_RESELLER_BRANCH;
    }

    public function isRegular(): bool
    {
        return $this->brand_type === self::TYPE_REGULAR;
    }
}
