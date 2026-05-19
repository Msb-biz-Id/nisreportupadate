<?php

namespace App\Models\Master;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanKain extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'bahan_kains';

    protected $fillable = ['nama', 'deskripsi', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q) { return $q->where('is_active', true); }
}
