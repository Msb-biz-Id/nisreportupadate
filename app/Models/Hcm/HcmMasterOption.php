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
        'uuid',
        'category_id',
        'parent_id',
        'name',
        'code',
        'order_index',
        'description',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Non-ID Base URL: Gunakan uuid sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'uuid', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'parent_id' => 'integer',
            'is_active' => 'boolean',
            'order_index' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HcmMasterCategory::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_index');
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
            'department' => ['departemen', 'department', 'departments'],
            'departments' => ['departemen', 'department', 'departments'],
            'departemen' => ['departemen', 'department', 'departments'],
            'divisi' => ['divisi', 'division', 'divisions'],
            'division' => ['divisi', 'division', 'divisions'],
            'divisions' => ['divisi', 'division', 'divisions'],
            'position' => ['divisi', 'division', 'divisions'],
            'positions' => ['divisi', 'division', 'divisions'],
            'posisi' => ['divisi', 'division', 'divisions'],
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
            'bank' => ['nama_bank', 'bank', 'banks'],
            'banks' => ['nama_bank', 'bank', 'banks'],
            'nama_bank' => ['nama_bank', 'bank', 'banks'],
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
            ->get(['hcm_master_options.id', 'hcm_master_options.name', 'hcm_master_options.code', 'hcm_master_options.parent_id', 'hcm_master_categories.code as cat_code'])
            ->groupBy('cat_code');

        $pick = function (...$keys) use ($all) {
            foreach ($keys as $k) {
                if ($all->has($k) && $all->get($k)->isNotEmpty()) {
                    return $all->get($k)->pluck('name')->values()->toArray();
                }
            }
            return [];
        };

        // Query dinamis Departemen & Divisi langsung dari database (Zero Hardcode)
        $deptCategory = HcmMasterCategory::whereIn('code', ['departemen', 'department', 'departments'])->first();
        $departments = $deptCategory
            ? $deptCategory->options()->where('is_active', true)->orderBy('order_index')->pluck('name')->toArray()
            : $pick('departemen', 'department', 'divisi');

        $divCategory = HcmMasterCategory::whereIn('code', ['divisi', 'division', 'divisions'])->first();
        $divisionOptions = $divCategory
            ? $divCategory->options()->with('parent')->where('is_active', true)->orderBy('order_index')->get()
            : collect();

        $divisions = $divisionOptions->pluck('name')->unique()->values()->toArray();

        $departmentDivisionMap = [];
        foreach ($divisionOptions as $divOpt) {
            $parentDeptName = $divOpt->parent?->name;
            if ($parentDeptName) {
                $departmentDivisionMap[$parentDeptName][] = $divOpt->name;
            }
        }

        return [
            'departments' => $departments,
            'divisions' => $divisions,
            'department_division_map' => $departmentDivisionMap,
            'job_levels' => $pick('job_level', 'job_levels'),
            'legal_entities' => $pick('entitas_cv', 'legal_entity', 'legal_entities'),
            'employment_statuses' => $pick('status_ketenagakerjaan', 'employment_status', 'employment_statuses'),
            'contract_reviews' => $pick('status_review_kontrak', 'status_kontrak', 'contract_reviews'),
            'genders' => $pick('jenis_kelamin', 'gender', 'genders') ?: ['Laki-Laki', 'Perempuan'],
            'religions' => $pick('agama', 'religion', 'religions') ?: ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'],
            'educations' => $pick('pendidikan_terakhir', 'education', 'educations') ?: ['SD / Sederajat', 'SMP / Sederajat', 'SMA / SMK / Sederajat', 'Diploma 3 (D3)', 'Strata 1 (S1)', 'Strata 2 (S2)'],
            'marital_statuses' => $pick('status_pernikahan', 'marital_status', 'marital_statuses') ?: ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'],
            'shirt_sizes' => $pick('ukuran_baju_seragam', 'shirt_size', 'shirt_sizes') ?: ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
            'status_lampiran' => $pick('status_lampiran') ?: ['Terlampir', 'Tidak Terlampir'],
            'kategori_potongan_gaji' => $pick('kategori_potongan_gaji') ?: ['Pelanggaran (Disciplinary Penalty)', 'Kelebihan Pengambilan Cuti (Leave Exceed)', 'Cuti Khusus Berjenjang (Maternity Leave)'],
            'notice_compliance' => $pick('kepatuhan_notice_period', 'notice_compliance', 'notice_periods'),
            'rights_status' => $pick('hak_karyawan', 'rights_status', 'employee_rights'),
            'asset_clearance' => $pick('pengembalian_aset_paklaring', 'asset_clearance'),
            'clearance_status' => $pick('status_clearance_sheet', 'clearance_status'),
            'banks' => $pick('nama_bank', 'bank', 'banks') ?: ['Bank BRI', 'Bank Mandiri', 'Bank BCA', 'Bank BNI', 'Bank Syariah Indonesia (BSI)', 'Bank Jateng', 'Tunai / Kas Kantor'],
            'departments_with_codes' => self::getDepartmentsWithCodes(),
            'tenure_buckets' => [
                ['value' => '<1_year', 'label' => 'Kurang dari 1 Tahun (< 12 bln)'],
                ['value' => '1_year', 'label' => 'Kelompok 1 Tahun (12 - 23 bln)'],
                ['value' => '2_years', 'label' => 'Kelompok 2 Tahun (24 - 35 bln)'],
                ['value' => '3_years', 'label' => 'Kelompok 3+ Tahun (>= 36 bln)'],
            ],
        ];
    }

    /**
     * Dapatkan daftar departemen beserta kode singkatannya (misal FIN, HCM, BRM, PRD).
     *
     * @return array<int, array{name: string, code: string}>
     */
    public static function getDepartmentsWithCodes(): array
    {
        return self::join('hcm_master_categories', 'hcm_master_options.category_id', '=', 'hcm_master_categories.id')
            ->whereIn('hcm_master_categories.code', ['departemen', 'department', 'departments', 'divisi'])
            ->where('hcm_master_options.is_active', true)
            ->orderBy('hcm_master_options.order_index')
            ->orderBy('hcm_master_options.name')
            ->get(['hcm_master_options.name', 'hcm_master_options.code'])
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'code' => strtoupper($item->code ?: \Illuminate\Support\Str::slug($item->name, '')),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Dapatkan kode departemen berdasarkan nama departemen.
     */
    public static function getDepartmentCodeByName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $option = self::join('hcm_master_categories', 'hcm_master_options.category_id', '=', 'hcm_master_categories.id')
            ->whereIn('hcm_master_categories.code', ['departemen', 'department', 'departments', 'divisi'])
            ->where('hcm_master_options.name', $name)
            ->first(['hcm_master_options.code']);

        return $option?->code ? strtoupper($option->code) : null;
    }
}
