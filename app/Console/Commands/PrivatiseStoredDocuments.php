<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * TEN-17 — moves client documents off the publicly-served disk.
 *
 * Supporting documents, AI-extraction source files and energy-certificate
 * documents used to be written to the `public` disk. Under tenancy that disk is
 * not reachable through the public/storage symlink — it lives in
 * storage/tenant{id}/app/public — so it is served instead by stancl's
 * /tenancy/assets/{path} route, whose only middleware is
 * InitializeTenancyBySubdomain. No `web`, no `auth`, no company check, and no
 * EnsureTenantIsActive: even a SUSPENDED client's files stayed downloadable to
 * anyone holding the URL.
 *
 * New uploads go to `local` and are served only through company-checked
 * controller actions. The readers still fall back to `public` so files uploaded
 * before that change keep working — which means the exposure is not actually
 * closed until those files are moved. This command moves them.
 *
 * Logos are deliberately NOT touched: company and app logos have to render on
 * the unauthenticated login screen, so `public` is where they belong.
 *
 * Run it for every client:
 *
 *     php artisan tenants:each "documents:privatise --pretend"
 *     php artisan tenants:each "documents:privatise"
 *
 * Afterwards the `public` fallbacks in EmissionRecordController::downloadDocument,
 * DocumentExtractionController::store and EnergyAttributeCertificateController::destroy
 * can be removed.
 */
class PrivatiseStoredDocuments extends Command
{
    use RequiresTenant;

    protected $signature = 'documents:privatise
        {--pretend : List what would move without touching anything}';

    protected $description = "Move a client's documents off the publicly-served disk onto private storage";

    /**
     * Prefixes holding client documents. Anything outside these — notably
     * company_logos/ and app/ — stays public on purpose.
     *
     * @var array<int, string>
     */
    private const PRIVATE_PREFIXES = [
        'supporting-documents',
        'energy-certificates',
    ];

    public function handle(): int
    {
        if (! $this->ensureTenantContext()) {
            return self::FAILURE;
        }

        $pretend = (bool) $this->option('pretend');
        $public = Storage::disk('public');
        $local = Storage::disk('local');

        $moved = 0;
        $skipped = 0;
        $failed = 0;

        foreach (self::PRIVATE_PREFIXES as $prefix) {
            if (! $public->exists($prefix)) {
                continue;
            }

            foreach ($public->allFiles($prefix) as $path) {
                // Already private: the reader would have found it on `local`
                // first, so the public copy is a leftover to delete, not move.
                if ($local->exists($path)) {
                    if ($pretend) {
                        $this->line("  <fg=yellow>duplicate</> {$path} (exists on local; public copy would be deleted)");
                    } else {
                        $public->delete($path);
                    }
                    $skipped++;

                    continue;
                }

                if ($pretend) {
                    $this->line("  <fg=green>move</>      {$path}");
                    $moved++;

                    continue;
                }

                // Copy-verify-delete rather than move: a half-completed move
                // would lose a client's audit evidence, and this runs against
                // files nobody has a second copy of.
                $contents = $public->get($path);

                if ($contents === null || ! $local->put($path, $contents)) {
                    $this->components->warn("Could not copy {$path}; left in place.");
                    $failed++;

                    continue;
                }

                if (! $local->exists($path)) {
                    $this->components->warn("Copy of {$path} did not land; left in place.");
                    $failed++;

                    continue;
                }

                $public->delete($path);
                $moved++;
            }
        }

        $tenant = tenant('id');
        $verb = $pretend ? 'would move' : 'moved';

        $this->components->info(
            "[{$tenant}] {$verb} {$moved} file(s); {$skipped} already private; {$failed} failed."
        );

        if ($pretend && ($moved > 0 || $skipped > 0)) {
            $this->components->warn('Nothing was changed. Re-run without --pretend to apply.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
