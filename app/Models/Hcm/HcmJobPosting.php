<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HcmJobPosting extends Model
{
    use HasFactory;

    protected $table = 'hcm_job_postings';

    protected $fillable = [
        'job_code',
        'title',
        'slug',
        'department',
        'legal_entity',
        'position',
        'job_type',
        'location',
        'quota',
        'fulfilled_count',
        'min_education',
        'min_experience_years',
        'salary_range',
        'salary_range_min',
        'salary_range_max',
        'description',
        'requirements',
        'benefits',
        'deadline',
        'start_date',
        'end_date',
        'recruitment_channel',
        'recruiter_id',
        'is_active',
        'status',
        'views_count',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deadline' => 'date:Y-m-d',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'quota' => 'integer',
        'fulfilled_count' => 'integer',
        'min_experience_years' => 'integer',
        'views_count' => 'integer',
        'salary_range_min' => 'decimal:2',
        'salary_range_max' => 'decimal:2',
    ];

    protected $appends = ['shareable_url'];

    /**
     * URL publik mandiri yang bisa disebarkan ke publik / pelamar.
     */
    protected function shareableUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => url("/karir/{$this->slug}")
        );
    }

    /**
     * Relasi ke seluruh pelamar yang melamar pada lowongan ini.
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(HcmJobApplicant::class, 'job_posting_id');
    }

    /**
     * Relasi ke user pembuat loker.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Auto generate slug saat pembuatan.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->slug)) {
                $job->slug = Str::slug($job->title) . '-' . Str::random(5);
            }
            if (empty($job->job_code)) {
                $year = date('Y');
                $lastId = self::max('id') ?? 0;
                $job->job_code = 'LKR-' . $year . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Non-ID Base URL: Gunakan slug sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'slug', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }
}
