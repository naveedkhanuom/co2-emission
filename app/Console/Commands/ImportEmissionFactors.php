<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Services\Factors\BuiltInFactorCatalog;
use App\Services\Factors\Import\ConfigCatalogueCompiler;
use App\Services\Factors\Import\DefraFlatFileImporter;
use Illuminate\Console\Command;

/**
 * Imports a published emission-factor dataset into a tenant's factor library.
 *
 *     php artisan tenants:each "factors:import defra --pretend"
 *     php artisan tenants:each "factors:import defra"
 *
 * The files live in database/factors/sources/, committed unmodified, with their
 * hashes and licences recorded in the MANIFEST there. Numbers come from the
 * publisher's own file and nowhere else — never transcribed, never from a
 * reseller's copy — so that "where did this come from" resolves to specific
 * bytes that can still be checked years later.
 *
 * NOT WIRED INTO PROVISIONING YET, deliberately. DEFRA 2026 alone is ~4,000
 * factors; provisioning already runs inline in the HTTP request at ~10s for 271.
 * Adding this to TenantDatabaseSeeder before TEN-07 moves provisioning to the
 * queue would push onboarding past the PHP-FPM timeout. Run it per tenant until
 * then.
 */
class ImportEmissionFactors extends Command
{
    use RequiresTenant;

    protected $signature = 'factors:import
        {dataset : Which dataset — "defra" or "builtin"}
        {--file= : Override the source file path}
        {--edition= : Override the edition, e.g. 2026. Not --version: Symfony Console reserves that}
        {--pretend : Parse and report without writing}';

    protected $description = "Import a published emission-factor dataset into this tenant's factor library";

    /**
     * @var array<string, array{file:string, version:string, label:string}>
     */
    private const DATASETS = [
        'defra' => [
            'file' => 'defra-2026-flat.xlsx',
            'version' => '2026',
            'label' => 'DEFRA/DESNZ 2026 (UK Government GHG Conversion Factors)',
        ],
    ];

    /**
     * Entries whose derived emission factor disagrees with the IPCC value their
     * own note quotes.
     *
     * Not a failure, and deliberately not corrected: `co2` is what prices every
     * figure today, so changing it would restate history — a decision for
     * whoever signs the inventory off, not for an importer. What it signals is
     * that `co2` and `ncv` are on different bases, almost always a gross
     * calorific value paired with a citation stated on a net one.
     *
     * It matters more under MRV than anywhere else: a regulator reads the
     * decomposition AND the citation, and here they do not agree with each
     * other.
     *
     * @param  array<int, array{source:string, unit:string, divergence:float}>  $divergent
     */
    private function reportDivergence(array $divergent): void
    {
        if ($divergent === []) {
            return;
        }

        $this->newLine();
        $this->components->warn(
            count($divergent).' decomposed '.str('factor')->plural(count($divergent))
            .' disagree with the IPCC value quoted in their own note by more than 1%. '
            .'Totals are unaffected — co2 is what prices them — but the NCV and the citation '
            .'are not on the same basis, which an assurer reading the MRV workbook will ask about.'
        );

        $this->newLine();
        $this->table(
            ['Source', 'Unit', 'Derived vs quoted'],
            collect($divergent)
                ->sortByDesc(fn ($row) => abs($row['divergence']))
                ->take(15)
                ->map(fn ($row) => [
                    $row['source'],
                    $row['unit'],
                    sprintf('%+.1f%%', $row['divergence']),
                ])
                ->all(),
        );

        if (count($divergent) > 15) {
            $this->components->info('… and '.(count($divergent) - 15).' more.');
        }
    }

    /**
     * Compile the built-in config catalogue into rows.
     *
     * Not a file import — the source is config/scope1_sources.php and
     * config/scope2_sources.php — but it belongs on the same command because it
     * is the same act: putting a published factor set into the library so one
     * resolver can answer for every scope.
     */
    private function compileBuiltIn(bool $pretend): int
    {
        $compiler = app(ConfigCatalogueCompiler::class);
        $catalog = app(BuiltInFactorCatalog::class);

        $tenant = tenant('id');
        $this->components->info("[{$tenant}] Built-in catalogue ".config('scope1_sources.version', 'unversioned'));

        $started = microtime(true);
        $result = $compiler->compile($catalog, $pretend);
        $elapsed = round(microtime(true) - $started, 1);

        $this->newLine();
        $this->components->twoColumnDetail('Catalogue entries', (string) $result['entries']);
        $this->components->twoColumnDetail($pretend ? 'Would write' : 'Factors written', (string) $result['written']);

        if (! $pretend) {
            $this->components->twoColumnDetail('Emission sources created', (string) $result['sources']);
            $this->components->twoColumnDetail('Previous factors superseded', (string) $result['superseded']);
        }

        // Entries whose citation names no publisher. Surfaced rather than
        // buried: each one is a factor whose provenance a human still has to
        // establish, and the number should go DOWN over time.
        $this->components->twoColumnDetail('Unattributed (no publisher cited)', (string) $result['unattributed']);

        // Rows carrying NCV + EF + oxidation, so the regulated MRV layer can
        // run the EU-ETS formula against them and a tier means something.
        $this->components->twoColumnDetail('Decomposed for MRV (NCV + EF)', (string) $result['decomposed']);
        $this->components->twoColumnDetail('Elapsed', $elapsed.'s');

        $this->reportDivergence($result['divergent'] ?? []);

        if ($pretend) {
            $this->newLine();
            $this->components->warn('Nothing was written. Re-run without --pretend to apply.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Recorded as factor_imports #'.$result['import']?->id.'.');

        return self::SUCCESS;
    }

    /** Current memory_limit in bytes; 0 or less means unlimited. */
    private function memoryLimitBytes(): int
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return 0;
        }

        $unit = mb_strtolower(mb_substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    public function handle(DefraFlatFileImporter $defra): int
    {
        if (! $this->ensureTenantContext()) {
            return self::FAILURE;
        }

        $dataset = mb_strtolower((string) $this->argument('dataset'));

        // The built-in catalogue is compiled from config rather than parsed from
        // a file, so it takes its own path — but it is the same act, and belongs
        // behind the same command.
        if ($dataset === 'builtin') {
            return $this->compileBuiltIn((bool) $this->option('pretend'));
        }

        if (! isset(self::DATASETS[$dataset])) {
            $this->components->error(
                "Unknown dataset \"{$dataset}\". Available: builtin, ".implode(', ', array_keys(self::DATASETS))
            );

            return self::FAILURE;
        }

        $spec = self::DATASETS[$dataset];
        $path = $this->option('file') ?: database_path('factors/sources/'.$spec['file']);
        $version = $this->option('edition') ?: $spec['version'];
        $pretend = (bool) $this->option('pretend');

        if (! is_file($path)) {
            $this->components->error(
                "Source file not found: {$path}\n"
                .'See database/factors/sources/MANIFEST.md for where it comes from.'
            );

            return self::FAILURE;
        }

        // Reading the DESNZ workbook costs ~66 MB through PhpSpreadsheet, and a
        // PHP CLI at the 128 MB default dies part-way through with an
        // "Allowed memory size exhausted" that names a library file and gives no
        // hint that the import is the cause. Raise it here rather than asking
        // every operator to remember a php.ini flag; the parse is cached per
        // process, so running this across a fleet reads the file once.
        if ($this->memoryLimitBytes() > 0 && $this->memoryLimitBytes() < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }

        $tenant = tenant('id');
        $this->components->info("[{$tenant}] {$spec['label']}");
        $this->line('  file: '.basename($path).'  ('.number_format(filesize($path)).' bytes)');
        $this->line('  sha256: '.hash_file('sha256', $path));

        $started = microtime(true);

        try {
            $result = $defra->import($path, $version, $pretend);
        } catch (\Throwable $e) {
            $this->components->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $started, 1);

        $this->newLine();
        $this->components->twoColumnDetail('Activity/unit pairs parsed', (string) $result['parsed']);
        $this->components->twoColumnDetail($pretend ? 'Would write' : 'Factors written', (string) $result['written']);

        if (! $pretend) {
            $this->components->twoColumnDetail('Emission sources created', (string) $result['sources']);
            $this->components->twoColumnDetail('Previous factors superseded', (string) $result['superseded']);
        }

        // Rows DEFRA publishes only a component or calorific value for. Reported
        // rather than silently dropped: a jump here means the workbook layout
        // changed, and a silent cap reads as "we imported everything".
        $this->components->twoColumnDetail('Skipped (no kg CO2e total)', (string) $result['skipped']);
        $this->components->twoColumnDetail('Elapsed', $elapsed.'s');

        if ($pretend) {
            $this->newLine();
            $this->components->warn('Nothing was written. Re-run without --pretend to apply.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Recorded as factor_imports #'.$result['import']?->id.'.');

        return self::SUCCESS;
    }
}
