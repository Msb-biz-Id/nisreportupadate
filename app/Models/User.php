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
        if ($this->isSuperadmin()) {
            return true;
        }
        return $this->brands()->where('brands.id', $brandId)->exists();
    }
}
