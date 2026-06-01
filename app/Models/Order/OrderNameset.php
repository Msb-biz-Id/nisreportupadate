<?php

namespace App\Models\Order;

use App\Models\Master\Size;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNameset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_item_id', 'nama_punggung', 'nomor_punggung',
        'nama_dada', 'nomor_dada', 'nama_lengan', 'nomor_lengan', 'nomor_punggung_2',
        'size_id', 'size_label', 'keterangan', 'urutan',
    ];

    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function size(): BelongsTo { return $this->belongsTo(Size::class); }
}
