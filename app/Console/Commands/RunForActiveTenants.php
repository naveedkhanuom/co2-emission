<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Throwable;

/**
 * Runs an artisan command inside each active tenant.
 *
 * Deliberately not stancl's tenants:run, which runs for EVERY tenant
 * regardless of status. A suspended or half-provisioned account would then
 * still have its scheduled reports generated and emailed, which is exactly
 * what suspending an account is supposed to stop.
 *
 * One tenant failing must not abort the rest: with a hundred clients, an
 * error in the third cannot be allowed to silently deny the other ninety-seven
 * their reports. Failures are collected, reported together, and reflected in
 * the exit code.
 */
class RunForActiveTenants extends Command
{
    /**
     * The argument is `commandname`, not `command`: Symfony Console reserves
     * `command` for the name of the command being run, and reusing it throws
     * "An argument with name command already exists". stancl's own tenants:run
     * uses the same spelling for the same reason.
     */
    protected $signature = 'tenants:each
        {commandname : The artisan command to run inside each tenant, with any arguments, e.g. "factors:import defra --pretend"}
        {--tenant=* : Limit to specific tenant ids. Default: every active tenant}';

    protected $description = 'Run an artisan command inside each active tenant';

    public function handle(): int
    {
        $tenants = $this->targetTenants();

        if ($tenants->isEmpty()) {
            $this->components->info('No active tenants to run for.');

            return self::SUCCESS;
        }

        $inner = (string) $this->argument('commandname');

        // The argument is a whole command line, not just a name: `schema:stamp
        // --check` and `factors:import defra --pretend` both need to work.
        //
        // $this->call() takes ($name, array $parameters) and treats anything else
        // as a name, so "schema:stamp --check" was looked up verbatim and failed
        // with "Command is not defined" — which reads like a missing command
        // rather than unparsed arguments. Its $parameters form does not help
        // either: positional arguments have to be keyed by the inner command's
        // own argument NAMES, which this command cannot know.
        //
        // StringInput is what the CLI itself uses, so the line is parsed exactly
        // as it would be at a shell prompt — positional, options, quoting and all.
        $failures = [];

        foreach ($tenants as $tenant) {
            $this->components->twoColumnDetail("<fg=cyan>{$tenant->id}</>", $tenant->name);

            try {
                $tenant->run(function () use ($inner) {
                    // doRun rather than run: run() catches exceptions and exits
                    // the process, which would abandon every tenant after this
                    // one. Failures belong to the catch below, which records them
                    // and carries on.
                    $status = $this->getApplication()->doRun(new StringInput($inner), $this->output);

                    if ($status !== 0) {
                        throw new RuntimeException("exited with status {$status}");
                    }
                });
            } catch (Throwable $e) {
                // Recorded and carried past, not rethrown: the remaining
                // tenants still need their run.
                $failures[$tenant->id] = $e->getMessage();
                $this->components->error("{$tenant->id}: {$e->getMessage()}");
            }
        }

        $ran = $tenants->count() - count($failures);
        $this->newLine();
        $this->components->info("{$inner} completed for {$ran} of {$tenants->count()} tenant(s).");

        if ($failures !== []) {
            $this->components->error(
                'Failed for: '.implode(', ', array_keys($failures)).'. The other tenants were unaffected.'
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Tenant>
     */
    protected function targetTenants()
    {
        return Tenant::query()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->when($this->option('tenant'), fn ($query, $ids) => $query->whereIn('id', $ids))
            ->orderBy('id')
            ->get();
    }
}
