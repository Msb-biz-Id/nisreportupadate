<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandTarget extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'brand_id',
        'year',
        'month',
        'target_revenue',
        'target_pcs',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'target_revenue' => 'decimal:2',
        'target_pcs' => 'integer',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
