<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $order_id
 * @property int|null $item_category_id
 * @property string $item_name
 * @property string|null $specification
 * @property string $unit
 * @property float $quantity
 * @property float $unit_price
 * @property float $discount_amount
 * @property float $subtotal
 * @property int $order_index
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Purchasing\PurchasingOrder|null $order
 * @property-read \App\Models\Purchasing\PurchasingMasterOption|null $itemCategory
 */
class PurchasingOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchasing_order_items';

    protected $fillable = [
        'uuid',
        'order_id',
        'item_category_id',
        'item_name',
        'specification',
        'unit',
        'quantity',
        'unit_price',
        'discount_amount',
        'subtotal',
        'order_index',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'order_index' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchasingOrder::class, 'order_id');
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(PurchasingMasterOption::class, 'item_category_id');
    }
}
