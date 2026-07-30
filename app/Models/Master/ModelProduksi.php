<?php
namespace App\Models\Master;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class ModelProduksi extends Model {
    use HasUuidAndSoftDeletes;
    protected $table = 'model_produksi';
    protected $fillable = ['nama', 'deskripsi', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function scopeActive(Builder $q) { return $q->where('is_active', true); }
}
