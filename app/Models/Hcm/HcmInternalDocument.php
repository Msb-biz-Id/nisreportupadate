<?php

namespace App\Models\Hcm;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $document_code
 * @property string $title
 * @property string|null $document_stage
 * @property string|null $category
 * @property string|null $department
 * @property string|null $revision_number
 * @property float|null $proposed_budget
 * @property float|null $actual_budget
 * @property Carbon|null $effective_date
 * @property Carbon|null $submission_date
 * @property Carbon|null $approval_date
 * @property string $status
 * @property string|null $file_url
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 */
class HcmInternalDocument extends Model
{
    use HasFactory;

    protected $table = 'hcm_internal_documents';

    protected $fillable = [
        'document_code',
        'title',
        'document_stage',
        'category',
        'department',
        'revision_number',
        'proposed_budget',
        'actual_budget',
        'effective_date',
        'submission_date',
        'approval_date',
        'status',
        'file_url',
        'description',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date:Y-m-d',
        'submission_date' => 'date:Y-m-d',
        'approval_date' => 'date:Y-m-d',
        'proposed_budget' => 'decimal:2',
        'actual_budget' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Non-ID Base URL: Gunakan document_code sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'document_code';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'document_code', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }
}
