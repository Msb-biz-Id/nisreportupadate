<?php

namespace App\Models\Master;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $fillable = ['kategori_size', 'ukuran', 'urutan', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'urutan' => 'integer'];

    public function scopeActive($q) { return $q->where('is_active', true); }
}
