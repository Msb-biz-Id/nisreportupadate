<?php

namespace App\Models\Finance;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KategoriPemasukan extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'kategori_pemasukan';

    protected $fillable = ['brand_id', 'nama_kategori', 'deskripsi', 'is_system', 'is_active'];

    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean'];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
}
