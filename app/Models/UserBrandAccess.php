<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBrandAccess extends Model
{
    protected $table = 'user_brand_access';

    protected $fillable = [
        'user_id',
        'brand_id',
        'is_default',
        'assigned_by',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
