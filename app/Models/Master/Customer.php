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

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForBrand(\Illuminate\Database\Eloquent\Builder $q, ?string $brandId): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where(function ($w) use ($brandId) {
            $w->where('brand_id', $brandId)->orWhereNull('brand_id');
        });
    }

    public static function generateUniqueKode(?string $brandId, string $prefix = 'CUST'): string
    {
        $lastCustomer = self::where('brand_id', $brandId)
            ->where('kode', 'like', $prefix . '-%')
            ->withTrashed()
            ->orderByDesc('kode')
            ->first();

        $nextNum = 1;
        if ($lastCustomer) {
            $lastNum = (int) str_replace($prefix . '-', '', $lastCustomer->kode);
            $nextNum = $lastNum + 1;
        }

        do {
            $custCode = $prefix . '-' . \Illuminate\Support\Str::padLeft((string) $nextNum, 5, '0');
            $exists = self::where('brand_id', $brandId)
                ->where('kode', $custCode)
                ->withTrashed()
                ->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $custCode;
    }
}

