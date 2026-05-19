<?php

namespace App\Models\Finance;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPengeluaran extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'kategori_pengeluaran';

    protected $fillable = ['brand_id', 'parent_id', 'nama_kategori', 'deskripsi', 'is_system', 'is_active'];

    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean'];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
}
