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
 * Removes the tenant's storage directory when the tenant is deleted.
 *
 * Deleting a tenant already drops its database. Leaving its uploads behind —
 * utility bills, OCR sources, supporting documents — would mean a client who
 * asked to be removed is still on disk, which is both untidy and the wrong
 * answer to give when they ask whether their data is gone.
 */
class DeleteTenantStorage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Tenant $tenant) {}

    public function handle(): void
    {
        $root = $this->tenant->storageRoot();

        if (File::isDirectory($root)) {
            File::deleteDirectory($root);
        }
    }
}
