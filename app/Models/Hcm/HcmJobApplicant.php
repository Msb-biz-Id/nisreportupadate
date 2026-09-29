<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HcmJobApplicant extends Model
{
    use HasFactory;

    protected $table = 'hcm_job_applicants';

    protected $fillable = [
        'job_posting_id',
        'applicant_code',
        'apply_date',
        'name',
        'nickname',
        'gender',
        'birth_place',
        'birth_date',
        'phone_number',
        'email',
        'education',
        'major',
        'address',
        'nik_ktp',
        'marital_status',
        'shirt_size',
        'expected_salary',
        'available_start_date',
        'experience_summary',
        'skills',
        'resume_file_url',
        'ktp_file_url',
        'portfolio_file_url',
        'photo',
        'photo_url',
        'status',
        'invitation_status',
        'interview_result',
        'onboarding_attendance',
        'is_blacklisted',
        'hcm_notes',
        'interview_date',
        'interview_location',
        'interviewer_notes',
        'rejection_reason',
        'converted_employee_id',
        'converted_at',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'available_start_date' => 'date:Y-m-d',
        'apply_date' => 'date:Y-m-d',
        'interview_date' => 'datetime',
        'converted_at' => 'datetime',
        'expected_salary' => 'decimal:2',
        'is_blacklisted' => 'boolean',
    ];

    protected $appends = [
        'photo_path',
        'photo_base64',
    ];

    /**
     * Dapatkan path fisik file foto di storage disk.
     */
    public function getPhotoPathAttribute(): ?string
    {
        if (!empty($this->photo)) {
            $path = storage_path('app/public/' . $this->photo);
            if (file_exists($path)) {
                return $path;
            }
        }

        if (!empty($this->photo_url)) {
            $cleanPath = str_replace('/storage/', '', $this->photo_url);
            if (file_exists(storage_path('app/public/' . $cleanPath))) {
                return storage_path('app/public/' . $cleanPath);
            }
            if (file_exists(public_path($cleanPath))) {
                return public_path($cleanPath);
            }
        }

        return null;
    }

    /**
     * Dapatkan data base64 foto pelamar.
     */
    public function getPhotoBase64Attribute(): ?string
    {
        $path = $this->photo_path;
        if (!$path || !file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(HcmJobPosting::class, 'job_posting_id');
    }

    /**
     * Rekap sesi wawancara kandidat (bisa multi-ronde).
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(HcmApplicantInterview::class, 'applicant_id')
            ->orderByDesc('interview_date')
            ->orderByDesc('id');
    }

    public function convertedEmployee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'converted_employee_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($applicant) {
            if (empty($applicant->applicant_code)) {
                $year = date('Y');
                $lastId = self::max('id') ?? 0;
                $number = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
                $applicant->applicant_code = "APL-{$year}-{$number}";
            }
            if (empty($applicant->apply_date)) {
                $applicant->apply_date = now()->toDateString();
            }
        });
    }
}
