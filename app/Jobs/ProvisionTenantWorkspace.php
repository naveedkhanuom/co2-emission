<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Provisioning a client account, off the web request.
 *
 * `tenant:provision` creates a database, runs 88 migrations, seeds roles,
 * permissions and several thousand emission factors, then creates the first
 * company and its owner. That takes 10–11 seconds on an idle machine. Behind a
 * typical 30-second PHP-FPM timeout, on a loaded server, a browser-initiated
 * onboarding eventually cuts out PART WAY THROUGH — leaving a database that
 * exists, a tenant row that may or may not, and an operator with no idea which.
 *
 * WHAT THIS IS NOT
 *
 * The obvious fix — flipping `shouldBeQueued(true)` on the TenantCreated
 * pipeline in TenancyServiceProvider — does not work, and the comment there
 * that suggests it is wrong. `ProvisionTenant` runs:
 *
 *     $tenant = Tenant::create([...]);        // fires TenantCreated
 *     $tenant->domains()->create([...]);
 *     $tenant->run(fn () => Company::create(...));   <-- needs the database
 *
 * Queue the pipeline and `Tenant::create()` returns before the database
 * exists, so the very next lines write into nothing. The pipeline has to stay
 * synchronous RELATIVE TO the thing provisioning the tenant.
 *
 * So what moves to the queue is the whole operation, not one step of it. Inside
 * this job everything runs in exactly the order it does on the command line.
 *
 * NOT RETRIED
 *
 * One attempt only. A second run would find the subdomain taken — by the
 * half-built account the first attempt left behind — and fail for a reason
 * that has nothing to do with why it failed the first time. Provisioning
 * already rolls itself back; a retry would fight that.
 */
class ProvisionTenantWorkspace implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 1;

    /**
     * Generous, because seeding the factor catalogues dominates and grows with
     * every library imported — but not unbounded, so a wedged provision
     * surfaces as a failed job rather than a worker that never comes back.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * @param  array<string, string|null>  $options  Command options, already validated by the caller.
     */
    public function __construct(
        public readonly string $subdomain,
        public readonly array $options,
    ) {}

    public function handle(): void
    {
        // Runs centrally: this job CREATES a tenant, so it must not be bound to
        // one. Dispatched from the back-office, where no tenant is initialised,
        // QueueTenancyBootstrapper puts no tenant id in the payload — this is
        // belt and braces for a future caller that dispatches from elsewhere.
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $exitCode = Artisan::call('tenant:provision', array_filter(
            array_merge(['subdomain' => $this->subdomain], $this->options),
            fn ($value) => $value !== null && $value !== '',
        ));

        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            // Thrown rather than returned, so the attempt lands in failed_jobs
            // where `queue:failed` will show it. A provisioning request that
            // quietly evaporates is the failure mode this job exists to end.
            throw new RuntimeException(
                "Provisioning \"{$this->subdomain}\" failed with exit code {$exitCode}: {$output}"
            );
        }

        Log::info('Tenant provisioned', ['subdomain' => $this->subdomain]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('Tenant provisioning job failed', [
            'subdomain' => $this->subdomain,
            'error' => $e->getMessage(),
        ]);
    }
}
