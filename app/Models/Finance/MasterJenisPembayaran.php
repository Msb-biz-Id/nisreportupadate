<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterJenisPembayaran extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $fillable = [
        'nama',
        'tipe_keuangan',
        'efek_tagihan',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
