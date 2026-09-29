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
        'category',
        'revision_number',
        'effective_date',
        'status',
        'file_url',
        'description',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date:Y-m-d',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
