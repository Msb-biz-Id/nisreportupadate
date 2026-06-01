<?php

namespace App\Models\Master;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisOrder extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'jenis_orders';

    protected $fillable = ['brand_id', 'nama', 'deskripsi', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }

    public function scopeForBrand($q, $brandId)
    {
        return $q->where(function ($w) use ($brandId) {
            $w->where('brand_id', $brandId)->orWhereNull('brand_id');
        });
    }
}
