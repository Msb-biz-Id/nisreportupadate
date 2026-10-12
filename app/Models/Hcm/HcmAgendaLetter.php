<?php

namespace App\Models\Hcm;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $letter_type
 * @property string $agenda_number
 * @property string|null $letter_number
 * @property Carbon|null $letter_date
 * @property Carbon|null $received_or_sent_date
 * @property string|null $sender
 * @property string|null $recipient
 * @property string $subject
 * @property string|null $category
 * @property string|null $disposition_status
 * @property string|null $file_url
 * @property string|null $physical_location
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 */
class HcmAgendaLetter extends Model
{
    use HasFactory;

    protected $table = 'hcm_agenda_letters';

    protected $fillable = [
        'letter_type',
        'agenda_number',
        'letter_number',
        'letter_date',
        'received_or_sent_date',
        'sender',
        'recipient',
        'subject',
        'category',
        'disposition_status',
        'file_url',
        'physical_location',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'letter_date' => 'date:Y-m-d',
        'received_or_sent_date' => 'date:Y-m-d',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($letter) {
            if (empty($letter->agenda_number)) {
                $year = date('Y');
                $lastId = self::max('id') ?? 0;
                $number = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
                $letter->agenda_number = "AGD-{$year}-{$number}";
            }
        });
    }

    /**
     * Non-ID Base URL: Gunakan agenda_number sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'agenda_number';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'agenda_number', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }
}
