<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
