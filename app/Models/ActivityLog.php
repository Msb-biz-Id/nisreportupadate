<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
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

    public function scopeForBrand($q, $brandId)
    {
        return $brandId ? $q->where('brand_id', $brandId) : $q;
    }
}
