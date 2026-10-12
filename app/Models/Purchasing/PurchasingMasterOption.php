<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $category
 * @property string|null $code
 * @property string $name
 * @property int $order_index
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PurchasingMasterOption extends Model
{
    use HasFactory;

    protected $table = 'purchasing_master_options';

    protected $fillable = [
        'uuid',
        'category',
        'code',
        'name',
        'order_index',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'uuid', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Dapatkan daftar opsi aktif berdasarkan nama kategori.
     *
     * @param string $category
     * @return array<string>
     */
    public static function getOptions(string $category): array
    {
        $categories = in_array(strtolower($category), ['lokasi', 'location'])
            ? ['location', 'lokasi']
            : [$category];

        return static::whereIn('category', $categories)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    /**
     * Dapatkan koleksi model opsi aktif berdasarkan nama kategori.
     *
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCategoryModels(string $category)
    {
        $categories = in_array(strtolower($category), ['lokasi', 'location'])
            ? ['location', 'lokasi']
            : [$category];

        return static::whereIn('category', $categories)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name', 'order_index', 'is_active']);
    }

    /**
     * Scope query untuk filter kategori dan status aktif.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        if (in_array(strtolower($category), ['lokasi', 'location'])) {
            return $query->whereIn('category', ['location', 'lokasi'])->where('is_active', true);
        }

        return $query->where('category', $category)->where('is_active', true);
    }
}
