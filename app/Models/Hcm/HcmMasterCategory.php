<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HcmMasterCategory extends Model
{
    use HasFactory;

    protected $table = 'hcm_master_categories';

    protected $fillable = [
        'code',
        'name',
        'group',
        'icon',
        'description',
        'order_index',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order_index' => 'integer',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(HcmMasterOption::class, 'category_id')->orderBy('order_index')->orderBy('id');
    }

    public function activeOptions(): HasMany
    {
        return $this->hasMany(HcmMasterOption::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('id');
    }

    /**
     * Non-ID Base URL: Gunakan code kategori sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'code', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }
}
