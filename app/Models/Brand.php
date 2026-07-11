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

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRegular(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('brand_type', self::TYPE_REGULAR);
    }

    public function scopeResellerHub(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('brand_type', self::TYPE_RESELLER_HUB);
    }

    public function scopeResellerBranch(\Illuminate\Database\Eloquent\Builder $query)
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

    public function isReseller(): bool
    {
        return ($this->isResellerHub() || $this->isResellerBranch()) && $this->parent_brand_id !== null;
    }

    public function isRegular(): bool
    {
        return $this->brand_type === self::TYPE_REGULAR;
    }

    public function getHeaderBrand(): Brand
    {
        if ($this->parent_brand_id) {
            $parent = $this->parentBrand()->first() ?? $this->parentBrand;
            if ($parent) {
                $thisIsReseller = $this->isResellerHub() || $this->isResellerBranch();
                $parentIsReseller = $parent->isResellerHub() || $parent->isResellerBranch();
                if ($thisIsReseller === $parentIsReseller) {
                    return $parent->getHeaderBrand();
                }
            }
        }

        if ($this->isResellerHub() || $this->isResellerBranch()) {
            $globalBrand = new self();
            $globalBrand->id = $this->id;
            
            $parent = $this->parent_brand_id ? ($this->parentBrand()->first() ?? $this->parentBrand) : null;
            $useParent = $parent && (($parent->isResellerHub() || $parent->isResellerBranch()) === ($this->isResellerHub() || $this->isResellerBranch()));

            $globalBrand->nama_brand = $this->nama_brand 
                ?: ($useParent ? $parent->nama_brand : null)
                ?: (\App\Models\Settings\SystemSetting::get('reseller_branding', 'nama_brand') 
                    ?: (\App\Models\Settings\SystemSetting::get('seo', 'site_name') ?: 'Circle Sportwear'));
            
            $resellerLogo = \App\Models\Settings\SystemSetting::get('reseller_branding', 'logo');
            if ($resellerLogo === 'logo.svg') {
                $resellerLogo = null;
            }

            $globalBrand->logo = $this->logo 
                ?: ($useParent ? $parent->logo : null)
                ?: $resellerLogo;
            
            $globalBrand->tagline = $this->tagline 
                ?: ($useParent ? $parent->tagline : null)
                ?: (\App\Models\Settings\SystemSetting::get('reseller_branding', 'tagline') 
                    ?: (\App\Models\Settings\SystemSetting::get('seo', 'site_description') ?: 'Premium Activewear & Apparel'));
            
            $globalBrand->email = $this->email 
                ?: ($useParent ? $parent->email : null)
                ?: (\App\Models\Settings\SystemSetting::get('reseller_branding', 'email') 
                    ?: (\App\Models\Settings\SystemSetting::get('mail', 'mail_from_address') ?: 'cs@circlesportwear.id'));
            
            $globalBrand->no_hp = $this->no_hp 
                ?: ($useParent ? $parent->no_hp : null)
                ?: (\App\Models\Settings\SystemSetting::get('reseller_branding', 'no_hp') 
                    ?: (\App\Models\Settings\SystemSetting::get('whatsapp', 'sender_phone') ?: '08123456789'));

            $globalBrand->alamat = $this->alamat 
                ?: ($useParent ? $parent->alamat : null)
                ?: \App\Models\Settings\SystemSetting::get('reseller_branding', 'alamat');

            $globalBrand->instagram = $this->instagram 
                ?: ($useParent ? $parent->instagram : null)
                ?: \App\Models\Settings\SystemSetting::get('reseller_branding', 'instagram');

            $globalBrand->tiktok = $this->tiktok 
                ?: ($useParent ? $parent->tiktok : null)
                ?: \App\Models\Settings\SystemSetting::get('reseller_branding', 'tiktok');

            $globalBrand->facebook = $this->facebook 
                ?: ($useParent ? $parent->facebook : null)
                ?: \App\Models\Settings\SystemSetting::get('reseller_branding', 'facebook');

            $globalBrand->website = $this->website 
                ?: ($useParent ? $parent->website : null)
                ?: \App\Models\Settings\SystemSetting::get('reseller_branding', 'website');
            
            $globalBrand->warna_primary = $this->warna_primary 
                ?: ($useParent ? $parent->warna_primary : null)
                ?: '#1E40AF';
            
            $globalBrand->alamat = $globalBrand->alamat ?: 'Bandung, Indonesia';
            
            return $globalBrand;
        }

        return $this;
    }
}

