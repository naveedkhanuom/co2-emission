<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Services\Factors\BuiltInFactorCatalog;
use App\Services\Factors\LibraryFactorCatalog;
use Illuminate\Console\Command;

/**
 * Are this tenant's compiled factor rows still what the catalogue produces?
 *
 * The built-in catalogue is authored in config/scope1_sources.php and
 * config/scope2_sources.php, and compiled into `emission_factors` by
 * `factors:import builtin`. Two representations of one set of numbers, and
 * only the config half is edited by hand.
 *
 * WHY A COMMAND AND NOT ONLY A TEST
 *
 * ConfigCatalogueCompileTest already proves "if you compile, the values match".
 * It compiles inside the test, so what it cannot tell you is whether the rows
 * in a REAL tenant's database match the config that is deployed alongside them.
 * Edit a factor, deploy without recompiling, and every screen that reads the
 * library — the factor list, reports, and now the EU-ETS decomposition the MRV
 * workbook is built from — serves yesterday's number while the Scope 1/2 entry
 * pages serve today's. Nothing looks wrong.
 *
 * So this runs where the answer actually matters: against a live tenant.
 *
 * Read only, and exits non-zero on drift so a deploy or a CI step can fail on
 * it rather than print into a log nobody reads.
 *
 *     php artisan tenants:each factors:check-drift
 */
class CheckFactorCatalogueDrift extends Command
{
    use RequiresTenant;

    protected $signature = 'factors:check-drift
        {--tolerance=0.000001 : Relative difference treated as agreement}';

    protected $description = "Check this tenant's compiled built-in factors against the current catalogue (read only)";

    public function handle(BuiltInFactorCatalog $catalog, LibraryFactorCatalog $library): int
    {
        $tolerance = (float) $this->option('tolerance');
        $tenant = tenant('id');

        $entries = $catalog->entries();
        $missing = [];
        $stale = [];
        $checked = 0;

        foreach ($entries as $entry) {
            $resolved = $library->resolve(
                $entry['scope'],
                $entry['is_grid'] ? BuiltInFactorCatalog::GRID_SOURCE : $entry['source'],
                $entry['unit'],
                $entry['is_grid'] ? ['region' => $entry['region']] : [],
            );

            // Never compiled, or compiled and then superseded by something that
            // no longer answers for it. Either way the library cannot price
            // what the entry page can.
            if ($resolved === null) {
                $missing[] = $this->label($entry);

                continue;
            }

            $checked++;

            if (! $this->agrees((float) $entry['value'], (float) $resolved->value, $tolerance)) {
                $stale[] = [
                    'entry' => $this->label($entry),
                    'config' => $entry['value'],
                    'stored' => $resolved->value,
                ];
            }
        }

        $this->components->info("[{$tenant}] ".count($entries).' catalogue entries · '.$checked.' resolved from the library');

        if ($missing === [] && $stale === []) {
            $this->components->info('No drift. The compiled rows match the catalogue.');

            return self::SUCCESS;
        }

        $this->reportMissing($missing);
        $this->reportStale($stale);

        $this->newLine();
        $this->components->error(
            'This tenant is serving factors that do not match the deployed catalogue. '
            .'Run: php artisan tenants:each "factors:import builtin"'
        );

        return self::FAILURE;
    }

    /**
     * Zero is compared exactly.
     *
     * A zero-carbon fuel really is zero, and a relative difference against zero
     * is either 0 or infinite — so a tolerance test on it would either wave
     * everything through or reject everything.
     */
    private function agrees(float $config, float $stored, float $tolerance): bool
    {
        if ($config === 0.0) {
            return $stored === 0.0;
        }

        return abs($stored - $config) / abs($config) <= $tolerance;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function label(array $entry): string
    {
        $name = $entry['is_grid']
            ? BuiltInFactorCatalog::GRID_SOURCE.' ('.$entry['region'].')'
            : $entry['source'];

        return $name.' ['.$entry['unit'].']';
    }

    /**
     * @param  array<int, string>  $missing
     */
    private function reportMissing(array $missing): void
    {
        if ($missing === []) {
            return;
        }

        $this->newLine();
        $this->components->warn(
            count($missing).' catalogue '.str('entry')->plural(count($missing))
            .' cannot be resolved from the library at all — most likely this tenant has never been compiled.'
        );

        foreach (array_slice($missing, 0, 10) as $label) {
            $this->components->twoColumnDetail($label, '<fg=yellow>not in the library</>');
        }

        if (count($missing) > 10) {
            $this->components->info('… and '.(count($missing) - 10).' more.');
        }
    }

    /**
     * @param  array<int, array{entry:string, config:mixed, stored:mixed}>  $stale
     */
    private function reportStale(array $stale): void
    {
        if ($stale === []) {
            return;
        }

        $this->newLine();
        $this->components->warn(
            count($stale).' stored '.str('factor')->plural(count($stale))
            .' disagree with the catalogue. The entry pages and the library are pricing the same '
            .'activity differently.'
        );

        $this->newLine();
        $this->table(
            ['Entry', 'Catalogue says', 'Database says'],
            collect($stale)->take(20)->map(fn ($row) => [
                $row['entry'],
                $this->trim($row['config']),
                $this->trim($row['stored']),
            ])->all(),
        );

        if (count($stale) > 20) {
            $this->components->info('… and '.(count($stale) - 20).' more.');
        }
    }

    private function trim(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 10, '.', ''), '0'), '.') ?: '0';
    }
}
