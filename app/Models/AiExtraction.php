<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

/**
 * One AI document-extraction run (Phase 3 audit trail). Holds the AI's proposal
 * and how much of it was saved as draft emission records.
 */
class AiExtraction extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'created_by',
        'file_name',
        'file_path',
        'document_type',
        'status',
        'items_count',
        'saved_count',
        'currency',
        'prompt_version',
        'result',
    ];

    protected $casts = [
        'result' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
