<?php

namespace App\Models\Purchasing;

use App\Models\Hcm\HcmEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

    public function assets(): HasMany
    {
        return $this->hasMany(PurchasingAsset::class, 'order_id');
    }
}
