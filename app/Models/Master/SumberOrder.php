<?php

namespace App\Models\Master;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Builder;

class SumberOrder extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'sumber_orders';

    protected $fillable = ['brand_id', 'parent_id', 'nama', 'deskripsi', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $appends = ['parent_nama'];

    public function getParentNamaAttribute(): ?string
    {
        return $this->parent?->nama;
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SumberOrder::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SumberOrder::class, 'parent_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForBrand(Builder $q, ?string $brandId): Builder
    {
        return $q->where(function (Builder $w) use ($brandId) {
            $w->where('brand_id', $brandId)->orWhereNull('brand_id');
        });
    }
}
