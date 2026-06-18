<?php

namespace App\Models\Order;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\Master\BankAccount;
use App\Models\User;
use App\Models\Master\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignDeposit extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $fillable = [
        'brand_id', 'customer_id', 'deposit_number', 'customer_name',
        'description', 'amount', 'payment_date', 'bank_id',
        'proof_file', 'notes', 'status', 'converted_to_order_id',
        'converted_at', 'recorded_by', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'converted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function bank(): BelongsTo { return $this->belongsTo(BankAccount::class, 'bank_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class, 'converted_to_order_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
