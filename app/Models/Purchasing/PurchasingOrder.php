<?php

namespace App\Models\Purchasing;

use App\Models\Hcm\HcmEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $po_number
 * @property \Illuminate\Support\Carbon|null $transaction_date
 * @property string $order_type
 * @property int|null $requester_employee_id
 * @property string|null $requester_name
 * @property string|null $department
 * @property string|null $division
 * @property string|null $position
 * @property int|null $location_id
 * @property int|null $vendor_id
 * @property string|null $vendor_name_manual
 * @property int|null $item_category_id
 * @property string $item_name
 * @property string|null $specification
 * @property string|null $unit
 * @property float $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property float $discount_amount
 * @property float $shipping_cost
 * @property float $tax_amount
 * @property float $grand_total
 * @property string $payment_type
 * @property string|null $invoice_number
 * @property string|null $invoice_file_path
 * @property string $status
 * @property int|null $pic_approved_by
 * @property \Illuminate\Support\Carbon|null $pic_approved_at
 * @property int|null $finance_approved_by
 * @property \Illuminate\Support\Carbon|null $finance_approved_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Purchasing\PurchasingVendor|null $vendor
 * @property-read \App\Models\Hcm\HcmEmployee|null $requester
 * @property-read \App\Models\Purchasing\PurchasingMasterOption|null $location
 * @property-read \App\Models\Purchasing\PurchasingMasterOption|null $itemCategory
 * @property-read \App\Models\User|null $picApprover
 * @property-read \App\Models\User|null $financeApprover
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Purchasing\PurchasingPayment[] $payments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Purchasing\PurchasingAsset[] $assets
 */
class PurchasingOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchasing_orders';

    protected $fillable = [
        'uuid',
        'po_number',
        'transaction_date',
        'order_type',
        'requester_employee_id',
        'requester_name',
        'department',
        'division',
        'position',
        'location_id',
        'vendor_id',
        'vendor_name_manual',
        'item_category_id',
        'item_name',
        'specification',
        'unit',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'tax_amount',
        'grand_total',
        'payment_type',
        'payment_mode',
        'invoice_number',
        'invoice_file_path',
        'status',
        'pic_approved_by',
        'pic_approved_at',
        'finance_approved_by',
        'finance_approved_at',
        'notes',
        'created_by',
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
        return 'po_number';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'po_number', $value)
            ->orWhere('uuid', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'pic_approved_at' => 'datetime',
            'finance_approved_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(PurchasingVendor::class, 'vendor_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'requester_employee_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PurchasingMasterOption::class, 'location_id');
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(PurchasingMasterOption::class, 'item_category_id');
    }

    public function picApprover(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'pic_approved_by');
    }

    public function financeApprover(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'finance_approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasingPayment::class, 'order_id')->orderBy('term_step');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchasingOrderItem::class, 'order_id')->orderBy('order_index');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(PurchasingAsset::class, 'order_id');
    }
}
