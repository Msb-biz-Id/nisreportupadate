<?php

namespace App\Models\Master;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketOrder extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'paket_orders';

    protected $fillable = ['nama', 'deskripsi', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q) { return $q->where('is_active', true); }
}
