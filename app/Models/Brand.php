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
        'timezone',
        'currency',
        'warna_primary',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
