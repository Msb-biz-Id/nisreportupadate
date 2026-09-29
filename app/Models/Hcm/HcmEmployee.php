<?php

namespace App\Models\Hcm;

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
