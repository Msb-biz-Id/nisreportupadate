<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HcmEmployee extends Model
{
    use HasFactory;

    protected $table = 'hcm_employees';

    protected $fillable = [
        'employee_code',
        'employee_category',
        'name',
        'nickname',
        'department',
        'position',
        'job_level',
        'employment_status',
        'legal_entity',
        'phone_number',
        'gender',
        'religion',
        'education',
        'marital_status',
        'birth_place',
        'birth_date',
        'nik_ktp',
        'bpjs_kesehatan_no',
        'bpjs_ketenagakerjaan_no',
        'shirt_size',
        'address',
        'bank_account_no',
        'bank_name',
        'email',
        'join_date',
        'is_active',
        'photo',
        'photo_url',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'photo_path',
        'photo_base64',
        'is_intern',
        'is_regular',
    ];

    /**
     * Non-ID Base URL: Gunakan employee_code sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'employee_code';
    }

    /**
     * Resolusi route binding dengan fallback aman.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'employee_code', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    /**
     * Scope untuk menyaring karyawan reguler (Managerial, Kontrak, Borongan, Harian).
     */
    public function scopeRegular(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('job_level', '!=', 'Magang')
              ->orWhereNull('job_level');
        })->where(function ($q) {
            $q->where('employee_category', '!=', 'Magang')
              ->where('employee_category', '!=', 'INTERN')
              ->orWhereNull('employee_category');
        })->whereDoesntHave('intern');
    }

    /**
     * Scope untuk menyaring peserta magang SMK (PKL).
     */
    public function scopeInterns(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('job_level', 'Magang')
              ->orWhere('employee_category', 'Magang')
              ->orWhere('employee_category', 'INTERN')
              ->orWhereHas('intern');
        });
    }

    /**
     * Accessor is_intern
     */
    public function getIsInternAttribute(): bool
    {
        return ($this->employee_category === 'INTERN')
            || ($this->employee_category === 'Magang')
            || ($this->job_level === 'Magang')
            || ($this->relationLoaded('intern') && $this->intern !== null);
    }

    /**
     * Accessor is_regular
     */
    public function getIsRegularAttribute(): bool
    {
        return ($this->employee_category ?? 'REGULAR') === 'REGULAR' && $this->job_level !== 'Magang';
    }

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
     * Dapatkan data base64 foto untuk rendering DomPDF.
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

    /**
     * Data Peserta Magang (jika job_level / status adalah Magang).
     */
    public function intern(): HasOne
    {
        return $this->hasOne(HcmIntern::class, 'employee_id');
    }

    /**
     * Rekap onboarding karyawan baru (Modul 12).
     */
    public function onboarding(): HasOne
    {
        return $this->hasOne(HcmOnboarding::class, 'employee_id');
    }

    /**
     * Rekap offboarding karyawan keluar (Modul 12).
     */
    public function offboarding(): HasOne
    {
        return $this->hasOne(HcmOffboarding::class, 'employee_id');
    }

    /**
     * Riwayat Kontrak Kerja PKWT.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(HcmContract::class, 'employee_id')->orderBy('contract_sequence', 'desc');
    }

    /**
     * Kontrak aktif saat ini.
     */
    public function activeContract(): HasOne
    {
        return $this->hasOne(HcmContract::class, 'employee_id')
            ->latestOfMany('contract_sequence');
    }

    /**
     * Data Kompensasi & Gaji/Honor.
     */
    public function compensation(): HasOne
    {
        return $this->hasOne(HcmCompensation::class, 'employee_id');
    }

    /**
     * Riwayat kenaikan gaji.
     */
    public function compensationHistories(): HasMany
    {
        return $this->hasMany(HcmCompensationHistory::class, 'employee_id')->orderBy('effective_date', 'desc');
    }

    /**
     * Riwayat presensi/kehadiran harian.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(HcmAttendance::class, 'employee_id')->orderBy('attendance_date', 'desc');
    }

    /**
     * Riwayat permohonan cuti / izin / sakit.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(HcmLeaveRequest::class, 'employee_id')->orderBy('start_date', 'desc');
    }

    /**
     * Riwayat lembur karyawan.
     */
    public function overtimes(): HasMany
    {
        return $this->hasMany(HcmOvertime::class, 'employee_id')->orderBy('overtime_date', 'desc');
    }

    /**
     * Riwayat uang makan bulanan.
     */
    public function mealAllowanceItems(): HasMany
    {
        return $this->hasMany(HcmMealAllowanceItem::class, 'employee_id');
    }

    /**
     * Rekapitulasi reward & apresiasi karyawan.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(HcmEmployeeReward::class, 'employee_id')->orderBy('reward_year', 'desc');
    }
}
