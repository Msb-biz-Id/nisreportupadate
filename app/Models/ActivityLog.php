<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use Prunable;

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(30));
    }
    protected $fillable = [
        'user_id', 'brand_id', 'activity', 'module',
        'subject_type', 'subject_id', 'description',
        'changes', 'ip_address', 'user_agent',
    ];

    protected $casts = ['changes' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function scopeForBrand(\Illuminate\Database\Eloquent\Builder $q, ?string $brandId)
    {
        return $brandId ? $q->where('brand_id', $brandId) : $q;
    }
}
