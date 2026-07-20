<?php

namespace App;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Drop-in audit trail for any Eloquent model.
 *
 * Add `use App\Auditable;` to a model and every create / update / delete is
 * recorded in the `audit_logs` table with a before/after diff, the acting user,
 * and request context. Writing the log never breaks the underlying save: any
 * failure is swallowed and logged.
 *
 * Customise per model with:
 *   protected array $auditExclude = ['some_noisy_column'];
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAuditLog('created', null, $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function ($model) {
            $changed = $model->auditableAttributes($model->getChanges());
            if (empty($changed)) {
                return; // nothing meaningful changed (e.g. only timestamps)
            }

            $old = [];
            foreach (array_keys($changed) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            $model->writeAuditLog('updated', $old, $changed);
        });

        static::deleted(function ($model) {
            $model->writeAuditLog('deleted', $model->auditableAttributes($model->getOriginal()), null);
        });

        // Plays nicely with soft-deletes if a model adds them later.
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->writeAuditLog('restored', null, $model->auditableAttributes($model->getAttributes()));
            });
        }
    }

    /**
     * Attributes that should never appear in the audit diff.
     */
    protected function auditExcludedAttributes(): array
    {
        return array_merge(
            ['created_at', 'updated_at'],
            property_exists($this, 'auditExclude') ? $this->auditExclude : []
        );
    }

    /**
     * Strip excluded keys from an attribute array.
     */
    protected function auditableAttributes(array $attributes): array
    {
        return array_diff_key($attributes, array_flip($this->auditExcludedAttributes()));
    }

    /**
     * Persist one audit row. Never throws — auditing must not block a save.
     */
    public function writeAuditLog(string $event, ?array $old, ?array $new): void
    {
        try {
            $user = auth()->user();

            $companyId = $this->company_id
                ?? (function_exists('current_company_id') ? current_company_id() : null);

            $request = request();

            AuditLog::create([
                'company_id'     => $companyId,
                'user_id'        => $user?->id,
                'user_name'      => $user?->name,
                'event'          => $event,
                'auditable_type' => static::class,
                'auditable_id'   => $this->getKey(),
                'old_values'     => $old,
                'new_values'     => $new,
                'url'            => $request ? mb_substr($request->fullUrl(), 0, 255) : null,
                'ip_address'     => $request?->ip(),
                'user_agent'     => $request ? mb_substr((string) $request->userAgent(), 0, 1000) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed: ' . $e->getMessage(), [
                'model' => static::class,
                'event' => $event,
            ]);
        }
    }

    /**
     * All audit rows for this model row, newest first.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }
}
