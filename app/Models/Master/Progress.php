<?php

namespace App\Models\Master;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Progress extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'progress';

    protected $fillable = ['nama_progress', 'warna', 'urutan', 'is_skippable', 'is_active'];

    protected $casts = [
        'is_skippable' => 'boolean',
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }

    public function scopeOrdered($q) { return $q->orderBy('urutan'); }
}
