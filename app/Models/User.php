<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'telegram_chat_id',
        'avatar',
        'is_active',
        'last_brand_id',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'two_factor_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function brandAccess(): HasMany
    {
        return $this->hasMany(UserBrandAccess::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'user_brand_access')
            ->withPivot(['is_default', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function defaultBrand()
    {
        return $this->brands()->wherePivot('is_default', true)->first()
            ?? $this->brands()->first();
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function hasAccessToBrand(string $brandId): bool
    {
        if ($brandId === 'all') {
            $canSeeAllGlobalBrands = $this->isSuperadmin() || $this->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);
            if ($canSeeAllGlobalBrands) {
                return true;
            }
            if ($this->hasRole('admin_reseller')) {
                return true;
            }
            return $this->brands()->count() > 1;
        }

        if ($this->isSuperadmin() || $this->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])) {
            return true;
        }

        if ($this->hasRole('admin_reseller')) {
            // Admin reseller automatically manages all reseller hubs, their branches, and IDW
            $brand = Brand::find($brandId);
            if ($brand) {
                if ($brand->kode === 'IDW') {
                    return true;
                }
                if ($brand->brand_type === Brand::TYPE_RESELLER_HUB) {
                    return true;
                }
                if ($brand->brand_type === Brand::TYPE_RESELLER_BRANCH && $brand->parent_brand_id) {
                    $parent = Brand::find($brand->parent_brand_id);
                    if ($parent && $parent->brand_type === Brand::TYPE_RESELLER_HUB) {
                        return true;
                    }
                }
            }
        }

        // Direct brand assignment
        if ($this->brands()->where('brands.id', $brandId)->exists()) {
            return true;
        }

        // Recursive parent brand access (if user has access to parent, they have access to child)
        $brand = Brand::find($brandId);
        if ($brand && $brand->parent_brand_id) {
            return $this->hasAccessToBrand($brand->parent_brand_id);
        }

        return false;
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }
}
