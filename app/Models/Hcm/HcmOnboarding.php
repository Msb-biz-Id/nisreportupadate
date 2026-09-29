<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmOnboarding extends Model
{
    use HasFactory;

    protected $table = 'hcm_onboardings';

    /** Kunci checklist onboarding beserta labelnya. */
    public const CHECKLIST = [
        'ktp'     => 'KTP / NIK',
        'bpjs'    => 'BPJS Kesehatan / Ketenagakerjaan',
        'kontrak' => 'Tanda Tangan Kontrak',
        'seragam' => 'Seragam Kerja',
    ];

    protected $fillable = [
        'employee_id',
        'position',
        'department',
        'join_date',
        'status_checklist',
        'approved_date',
    ];

    protected function casts(): array
    {
        return [
            'status_checklist' => 'array',
            'join_date' => 'date',
            'approved_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * Apakah seluruh checklist onboarding sudah lengkap.
     */
    public function isComplete(): bool
    {
        $checklist = $this->status_checklist ?? [];

        foreach (array_keys(self::CHECKLIST) as $key) {
            if (empty($checklist[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sinkronkan rekap onboarding dengan kondisi terkini data karyawan.
     * Checklist yang sudah diisi manual tetap dipertahankan (OR dengan hasil
     * deteksi otomatis) agar kelengkapan tidak bisa diturunkan secara palsu.
     */
    public static function syncFor(HcmEmployee $employee): self
    {
        $derived = [
            'ktp'     => !empty($employee->nik_ktp) && !str_starts_with((string) $employee->nik_ktp, 'AUTO-'),
            'bpjs'    => !empty($employee->bpjs_kesehatan_no) || !empty($employee->bpjs_ketenagakerjaan_no),
            'kontrak' => $employee->contracts()->exists(),
            'seragam' => !empty($employee->shirt_size),
        ];

        /** @var self $onboarding */
        $onboarding = self::firstOrNew(['employee_id' => $employee->id]);
        $existing = $onboarding->status_checklist ?? [];

        $merged = [];
        foreach ($derived as $key => $value) {
            $merged[$key] = (bool) (($existing[$key] ?? false) || $value);
        }

        $onboarding->position = $onboarding->position ?: $employee->position;
        $onboarding->department = $onboarding->department ?: $employee->department;
        $onboarding->join_date = $onboarding->join_date ?: $employee->join_date;
        $onboarding->status_checklist = $merged;

        if (!in_array(false, $merged, true) && empty($onboarding->approved_date)) {
            $onboarding->approved_date = now()->toDateString();
        }

        $onboarding->save();

        return $onboarding;
    }
}
