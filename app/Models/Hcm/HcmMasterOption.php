<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmMasterOption extends Model
{
    use HasFactory;

    protected $table = 'hcm_master_options';

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'order_index',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'is_active' => 'boolean',
            'order_index' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HcmMasterCategory::class, 'category_id');
    }

    /**
     * Dapatkan daftar nama opsi aktif berdasarkan kode atau nama kategori.
     *
     * @param string|array $categoryCodes
     * @return array<string>
     */
    public static function getOptions(string|array $categoryCodes): array
    {
        $codes = is_array($categoryCodes) ? $categoryCodes : [$categoryCodes];

        $synonyms = [
            'department' => ['divisi', 'department', 'departments'],
            'departments' => ['divisi', 'department', 'departments'],
            'divisi' => ['divisi', 'department', 'departments'],
            'position' => ['posisi', 'position', 'positions'],
            'positions' => ['posisi', 'position', 'positions'],
            'posisi' => ['posisi', 'position', 'positions'],
            'job_level' => ['job_level', 'job_levels', 'level_jabatan'],
            'job_levels' => ['job_level', 'job_levels', 'level_jabatan'],
            'employment_status' => ['status_ketenagakerjaan', 'employment_status', 'employment_statuses'],
            'employment_statuses' => ['status_ketenagakerjaan', 'employment_status', 'employment_statuses'],
            'status_ketenagakerjaan' => ['status_ketenagakerjaan', 'employment_status', 'employment_statuses'],
            'legal_entity' => ['entitas_cv', 'legal_entity', 'legal_entities'],
            'legal_entities' => ['entitas_cv', 'legal_entity', 'legal_entities'],
            'entitas_cv' => ['entitas_cv', 'legal_entity', 'legal_entities'],
            'status_kontrak' => ['status_review_kontrak', 'status_kontrak', 'contract_reviews', 'review_status'],
            'status_review_kontrak' => ['status_review_kontrak', 'status_kontrak', 'contract_reviews', 'review_status'],
            'contract_reviews' => ['status_review_kontrak', 'status_kontrak', 'contract_reviews', 'review_status'],
            'gender' => ['jenis_kelamin', 'gender', 'genders'],
            'genders' => ['jenis_kelamin', 'gender', 'genders'],
            'jenis_kelamin' => ['jenis_kelamin', 'gender', 'genders'],
            'religion' => ['agama', 'religion', 'religions'],
            'religions' => ['agama', 'religion', 'religions'],
            'agama' => ['agama', 'religion', 'religions'],
            'education' => ['pendidikan_terakhir', 'education', 'educations'],
            'educations' => ['pendidikan_terakhir', 'education', 'educations'],
            'pendidikan_terakhir' => ['pendidikan_terakhir', 'education', 'educations'],
            'marital_status' => ['status_pernikahan', 'marital_status', 'marital_statuses'],
            'marital_statuses' => ['status_pernikahan', 'marital_status', 'marital_statuses'],
            'status_pernikahan' => ['status_pernikahan', 'marital_status', 'marital_statuses'],
            'shirt_size' => ['ukuran_baju_seragam', 'shirt_size', 'shirt_sizes'],
            'shirt_sizes' => ['ukuran_baju_seragam', 'shirt_size', 'shirt_sizes'],
            'ukuran_baju_seragam' => ['ukuran_baju_seragam', 'shirt_size', 'shirt_sizes'],
        ];

        $expandedCodes = [];
        foreach ($codes as $c) {
            if (isset($synonyms[$c])) {
                $expandedCodes = array_merge($expandedCodes, $synonyms[$c]);
            } else {
                $expandedCodes[] = $c;
            }
        }
        $expandedCodes = array_unique($expandedCodes);

        return self::join('hcm_master_categories', 'hcm_master_options.category_id', '=', 'hcm_master_categories.id')
            ->whereIn('hcm_master_categories.code', $expandedCodes)
            ->where('hcm_master_options.is_active', true)
            ->orderBy('hcm_master_options.order_index')
            ->orderBy('hcm_master_options.name')
            ->pluck('hcm_master_options.name')
            ->toArray();
    }

    /**
     * Dapatkan semua opsi master data lengkap untuk form karyawan & kontrak secara dinamis.
     */
    public static function getAllDropdowns(): array
    {
        $all = self::join('hcm_master_categories', 'hcm_master_options.category_id', '=', 'hcm_master_categories.id')
            ->where('hcm_master_options.is_active', true)
            ->orderBy('hcm_master_options.order_index')
            ->orderBy('hcm_master_options.name')
            ->get(['hcm_master_options.name', 'hcm_master_categories.code as cat_code'])
            ->groupBy('cat_code');

        $pick = function (...$keys) use ($all) {
            foreach ($keys as $k) {
                if ($all->has($k) && $all->get($k)->isNotEmpty()) {
                    return $all->get($k)->pluck('name')->values()->toArray();
                }
            }
            return [];
        };

        return [
            'departments' => $pick('divisi', 'department', 'departments'),
            'job_levels' => $pick('job_level', 'job_levels'),
            'positions' => $pick('posisi', 'position', 'positions'),
            'legal_entities' => $pick('entitas_cv', 'legal_entity', 'legal_entities'),
            'employment_statuses' => $pick('status_ketenagakerjaan', 'employment_status', 'employment_statuses'),
            'contract_reviews' => $pick('status_review_kontrak', 'status_kontrak', 'contract_reviews'),
            'genders' => $pick('jenis_kelamin', 'gender', 'genders') ?: ['Laki-Laki', 'Perempuan'],
            'religions' => $pick('agama', 'religion', 'religions') ?: ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'],
            'educations' => $pick('pendidikan_terakhir', 'education', 'educations') ?: ['SD / Sederajat', 'SMP / Sederajat', 'SMA / SMK / Sederajat', 'Diploma 3 (D3)', 'Strata 1 (S1)', 'Strata 2 (S2)'],
            'marital_statuses' => $pick('status_pernikahan', 'marital_status', 'marital_statuses') ?: ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'],
            'shirt_sizes' => $pick('ukuran_baju_seragam', 'shirt_size', 'shirt_sizes') ?: ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
            'banks' => ['Bank BRI', 'Bank Mandiri', 'Bank BCA', 'Bank BNI', 'BSI', 'Bank Jateng', 'Tunai / Kas'],
        ];
    }
}
