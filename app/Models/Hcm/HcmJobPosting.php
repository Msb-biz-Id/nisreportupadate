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
        'title',
        'slug',
        'department',
        'position',
        'job_type',
        'location',
        'quota',
        'min_education',
        'min_experience_years',
        'salary_range',
        'description',
        'requirements',
        'benefits',
        'deadline',
        'is_active',
        'views_count',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deadline' => 'date:Y-m-d',
        'quota' => 'integer',
        'min_experience_years' => 'integer',
        'views_count' => 'integer',
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
        });
    }
}
