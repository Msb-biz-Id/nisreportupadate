<?php

namespace App\Models\Master;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $fillable = [
        'brand_id', 'kode', 'nama', 'nomor_hp', 'email',
        'type_pelanggan_id', 'sumber_daftar_id',
        'provinsi_code', 'provinsi_nama',
        'kabupaten_code', 'kabupaten_nama',
        'kecamatan_code', 'kecamatan_nama',
        'desa_code', 'desa_nama',
        'detail_alamat', 'kodepos',
        'notes', 'total_order', 'total_transaksi', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_order' => 'integer',
        'total_transaksi' => 'decimal:2',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'type_pelanggan_id');
    }

    public function sumberOrder(): BelongsTo
    {
        return $this->belongsTo(SumberOrder::class, 'sumber_daftar_id');
    }

    public function scopeActive($q) { return $q->where('is_active', true); }

    public function scopeForBrand($q, $brandId)
    {
        return $q->where(function ($w) use ($brandId) {
            $w->where('brand_id', $brandId)->orWhereNull('brand_id');
        });
    }
}
