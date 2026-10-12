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
 * @property string|null $invoice_number
 * @property int $term_step
 * @property string|null $term_name
 * @property float|null $term_percentage
 * @property float $amount
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $payment_method
 * @property string|null $receipt_file_path
 * @property int|null $verified_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Purchasing\PurchasingOrder|null $order
 * @property-read \App\Models\User|null $verifier
 */
class PurchasingPayment extends Model
{
    use HasFactory;

    protected $table = 'purchasing_payments';

    protected $fillable = [
        'uuid',
        'order_id',
        'invoice_number',
        'term_step',
        'term_name',
        'term_percentage',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'payment_method',
        'receipt_file_path',
        'verified_by',
        'notes',
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
            'term_step' => 'integer',
            'term_percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchasingOrder::class, 'order_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }
}
