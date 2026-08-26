<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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

    /** Import reference format: IMP- followed by at least four digits. */
    private const IMPORT_ID_PREFIX = 'IMP-';

    /** How many times to retry when another import claims the same id first. */
    private const ID_ATTEMPTS = 5;

    /**
     * The next import reference.
     *
     * Derived from the highest number in use rather than from the most recent
     * row, because "most recent" is not the same as "highest": one malformed
     * value read as 0 by the old (int) cast would restart the sequence at 1 and
     * collide with IMP-0001 on every subsequent import.
     *
     * Deliberately unscoped by company — import_id carries a UNIQUE index across
     * all tenants, so the number has to be global.
     *
     * Still racy on its own: two imports reading at the same moment get the same
     * answer. That is what startNew() handles.
     */
    public static function generateImportId(): string
    {
        $highest = (int) DB::table('import_history')
            ->whereRaw("import_id REGEXP '^".self::IMPORT_ID_PREFIX."[0-9]+$'")
            ->selectRaw('MAX(CAST(SUBSTRING(import_id, 5) AS UNSIGNED)) AS highest')
            ->value('highest');

        return self::IMPORT_ID_PREFIX.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create an import history row, claiming the next free reference.
     *
     * Two concurrent imports read the same "next" number, and the UNIQUE index
     * on import_id means the second one's insert fails. Left alone that surfaces
     * to the user as a 500 on an otherwise valid upload, so the loser of the race
     * simply re-reads and takes the next number instead.
     *
     * Only a duplicate-key failure is retried; anything else is a real error and
     * is rethrown untouched.
     */
    public static function startNew(array $attributes): self
    {
        for ($attempt = 1; $attempt <= self::ID_ATTEMPTS; $attempt++) {
            try {
                return self::create(array_merge($attributes, [
                    'import_id' => self::generateImportId(),
                ]));
            } catch (QueryException $e) {
                if ($attempt === self::ID_ATTEMPTS || ! self::isDuplicateKey($e)) {
                    throw $e;
                }
            }
        }

        // Unreachable: the loop either returns or rethrows.
        throw new \RuntimeException('Could not allocate an import reference.');
    }

    /** MySQL 1062 / SQLSTATE 23000 — unique constraint violation. */
    private static function isDuplicateKey(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062
            || str_contains($e->getMessage(), 'Duplicate entry');
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
