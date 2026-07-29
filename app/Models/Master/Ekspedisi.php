<?php

namespace App\Models\Master;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $nama
 * @property string|null $deskripsi
 * @property bool $is_active
 *
 * @mixin Model
 */
class Ekspedisi extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'ekspedisi';

    protected $fillable = ['nama', 'deskripsi', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
