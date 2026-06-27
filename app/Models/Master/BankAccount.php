<?php

namespace App\Models\Master;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $fillable = ['brand_id', 'bank', 'atas_nama', 'nomor_rekening', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getAtasNamaAttribute(?string $value)
    {
        if ($this->bank === 'CASH') {
            return 'Cash';
        }

        try {
            if (request() && request()->hasSession()) {
                $currentBrandId = session(\App\Support\BrandContext::SESSION_KEY) ?? (\Illuminate\Support\Facades\Auth::user()?->last_brand_id);
                if ($currentBrandId) {
                    $currentBrand = Brand::find($currentBrandId);
                    if ($currentBrand && $currentBrand->isReseller()) {
                        return $currentBrand->nama_brand;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback during console command/seeding/tests without session
        }

        return $value;
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $q) { return $q->where('is_active', true); }

    public function scopeForBrand(\Illuminate\Database\Eloquent\Builder $q, ?string $brandId)
    {
        return $q->where('brand_id', $brandId);
    }
}
