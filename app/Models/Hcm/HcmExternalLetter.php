<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmExternalLetter extends Model
{
    use HasFactory;

    protected $table = 'hcm_external_letters';

    protected $fillable = [
        'registration_no',
        'letter_date',
        'direction',
        'external_letter_no',
        'sender',
        'recipient',
        'subject',
        'file_scan_url',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'letter_date' => 'date:Y-m-d',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($letter) {
            if (empty($letter->registration_no)) {
                $year = date('Y');
                $lastId = self::max('id') ?? 0;
                $letter->registration_no = 'DOC-EXT-' . $year . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
