<?php

namespace App\Models\Master;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketOrder extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'paket_orders';

    protected $fillable = ['nama', 'deskripsi', 'warna', 'prioritas', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'prioritas'  => 'integer',
    ];

    /** 0=normal, 1=ekspress, 2=urgent */
    public function isExpress(): bool { return $this->prioritas >= 1; }
    public function isUrgent(): bool  { return $this->prioritas >= 2; }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
