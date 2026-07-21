<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class ImportHistory extends Model
{
    use HasFactory, HasCompanyScope;

    protected $table = 'import_history';

    protected $fillable = [
        'company_id',
        'import_id',
        'file_name',
        'file_path',
        'file_size',
        'import_type',
        'status',
        'total_records',
        'successful_records',
        'failed_records',
        'warning_records',
        'processing_time',
        'logs',
        'error_message',
        'metadata',
        'user_id',
        'started_at',
        'completed_at',
    ];

    protected $appends = ['formatted_file_size'];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
        'total_records' => 'integer',
        'successful_records' => 'integer',
        'failed_records' => 'integer',
        'warning_records' => 'integer',
        'processing_time' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // Helper method to generate import ID.
    // Generated globally (ignoring the company scope) so import_id stays unique across all tenants.
    public static function generateImportId(): string
    {
        $lastImport = self::withoutGlobalScope('company')->latest('id')->first();
        $number = $lastImport ? ((int) str_replace('IMP-', '', $lastImport->import_id)) + 1 : 1;
        return 'IMP-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // Get formatted file size
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    // Get parsed logs as array
    public function getParsedLogsAttribute(): array
    {
        if (!$this->logs) {
            return [];
        }

        $decoded = json_decode($this->logs, true);
        return $decoded ?: [];
    }
}
