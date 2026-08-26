<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

/**
 * Creates the tenant's storage directories.
 *
 * FilesystemTenancyBootstrapper suffixes storage_path() to
 * storage/tenant{id}/ while tenancy is initialised, and points the local and
 * public disks inside it — but nothing creates those directories. The first
 * upload, the first compiled Blade view, or the first log line then fails on
 * a missing path, and it fails at use time rather than at provisioning time,
 * which is the worst moment to discover it.
 *
 * The tree mirrors Laravel's own storage skeleton, because everything
 * underneath storage_path() moves with the suffix — not just uploads.
 */
class CreateTenantStorage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Paths relative to the tenant's storage root.
     *
     * @var array<int, string>
     */
    protected const DIRECTORIES = [
        'app',
        'app/public',
        'framework/cache/data',
        'framework/sessions',
        'framework/views',
        'logs',
    ];

    public function __construct(protected Tenant $tenant) {}

    public function handle(): void
    {
        $root = $this->tenant->storageRoot();

        foreach (self::DIRECTORIES as $directory) {
            File::ensureDirectoryExists($root.'/'.$directory);
        }
    }
}
