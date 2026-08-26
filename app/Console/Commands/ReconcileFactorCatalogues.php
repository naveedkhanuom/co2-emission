<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Models\EmissionFactor;
use App\Services\Factors\BuiltInFactorCatalog;
use Illuminate\Console\Command;

/**
 * Compares the two emission-factor libraries and reports where they disagree.
 *
 * The platform carries the same factors twice: the `emission_factors` table used
 * by Manual Entry, and the built-in catalogues in config/scope1_sources.php and
 * config/scope2_sources.php shipped to the Scope 1/2 entry pages. Nothing keeps
 * them in step, and they have already drifted — the same fuel in the same unit
 * yields a different figure depending on which screen was used.
 *
 * READ ONLY. This writes nothing. Reconciling the conflicts means choosing which
 * coal rank or which refrigerant blend a factor represents, which is an
 * accounting decision with restatement consequences — it belongs to a person,
 * not to a script. This produces the list that person needs.
 */
class ReconcileFactorCatalogues extends Command
{
    use RequiresTenant;

    protected $signature = 'factors:reconcile
                            {--tolerance=0.01 : Relative difference treated as agreement (0.01 = 1%)}
                            {--all : Also list the entries that agree}
                            {--output= : Write a markdown report to this path}';

    protected $description = 'Compare the built-in factor catalogues against the emission_factors table (read only)';

    /**
     * Unit spellings that mean the same thing across the two libraries.
     *
     * Deliberately conservative: 'ton' is left alone because the DB uses it for
     * short tons while the Scope 2 catalogue uses it for tonnes of steam. A
     * false match there would hide a real disagreement, so ambiguous spellings
     * are reported as unmatched instead.
     */
    private const UNIT_ALIASES = [
        'm³' => 'm3',
        'litres' => 'liters',
        'l' => 'liters',
        'tonne' => 'tonnes',
    ];

    private array $lines = [];

    public function handle(BuiltInFactorCatalog $catalog): int
    {
        if (! $this->ensureTenantContext()) {
            return self::FAILURE;
        }

        $tolerance = (float) $this->option('tolerance');

        $config = $this->configEntries($catalog);
        $database = $this->databaseEntries();

        $conflicts = [];
        $agreements = [];
        $onlyInConfig = [];
        $matchedDbKeys = [];

        foreach ($config as $key => $entry) {
            if (! isset($database[$key])) {
                $onlyInConfig[] = $entry;

                continue;
            }

            $matchedDbKeys[$key] = true;
            $db = $database[$key];

            $drift = $entry['value'] > 0
                ? abs($db['value'] - $entry['value']) / $entry['value']
                : ($db['value'] > 0 ? 1.0 : 0.0);

            $row = $entry + ['db_value' => $db['value'], 'db_reference' => $db['reference'], 'drift' => $drift];

            if ($drift > $tolerance) {
                $conflicts[] = $row;
            } else {
                $agreements[] = $row;
            }
        }

        $onlyInDatabase = array_values(array_diff_key($database, $matchedDbKeys));

        usort($conflicts, fn ($a, $b) => $b['drift'] <=> $a['drift']);

        $this->render($config, $database, $conflicts, $agreements, $onlyInConfig, $onlyInDatabase, $tolerance);

        if ($path = $this->option('output')) {
            file_put_contents($path, implode(PHP_EOL, $this->lines).PHP_EOL);
            $this->info('Written to '.$path);
        }

        return self::SUCCESS;
    }

    /**
     * Every priceable (source, unit) pair in the built-in catalogues.
     *
     * Scope 2 grid sources are excluded: their factor depends on which of the 23
     * grid regions is selected, so there is no single value to compare against a
     * DB row that carries no region. They are counted and reported separately.
     */
    private function configEntries(BuiltInFactorCatalog $catalog): array
    {
        $entries = [];
        $this->gridSourceCount = 0;

        foreach (['stationary', 'mobile', 'fugitive'] as $group) {
            foreach (config("scope1_sources.{$group}", []) as $src) {
                foreach ($src['units'] ?? [] as $unit) {
                    $resolved = $catalog->resolve(1, $src['name'], $unit['u']);
                    if ($resolved === null) {
                        continue;
                    }

                    $entries[$this->key($src['name'], $unit['u'])] = [
                        'scope' => 1,
                        'source' => $src['name'],
                        'unit' => $unit['u'],
                        'value' => $resolved->value,
                        'reference' => $resolved->reference,
                    ];
                }
            }
        }

        foreach (['electricity', 'heating', 'cooling'] as $group) {
            foreach (config("scope2_sources.{$group}", []) as $src) {
                if (! empty($src['isGrid'])) {
                    $this->gridSourceCount++;

                    continue;
                }

                foreach ($src['units'] ?? [] as $unit) {
                    $resolved = $catalog->resolve(2, $src['name'], $unit['u']);
                    if ($resolved === null) {
                        continue;
                    }

                    $entries[$this->key($src['name'], $unit['u'])] = [
                        'scope' => 2,
                        'source' => $src['name'],
                        'unit' => $unit['u'],
                        'value' => $resolved->value,
                        'reference' => $resolved->reference,
                    ];
                }
            }
        }

        return $entries;
    }

    private int $gridSourceCount = 0;

    /**
     * The database library's comparable baseline per (source, unit).
     *
     * A source+unit legitimately has several rows — one per region, which is the
     * point of country-specific factors. The built-in catalogue has no region
     * concept, so the only fair comparison is against the generic row.
     *
     * Preference order: an explicitly generic region ("default", "Global",
     * "placeholder"), then a row with no region at all, then — only if a source
     * has nothing generic — the sole row if there is exactly one. A source that
     * has several region-specific rows and no generic one is reported as
     * region-only rather than compared against an arbitrary one, which is the
     * mistake that made a UK DEFRA coal factor look like a 34% conflict.
     */
    private function databaseEntries(): array
    {
        $grouped = [];

        foreach (EmissionFactor::withoutGlobalScopes()->with(['emissionSource', 'organization'])->get() as $factor) {
            $name = $factor->emissionSource->name ?? null;
            if (! $name) {
                continue;
            }

            $grouped[$this->key($name, (string) $factor->unit)][] = $factor;
        }

        $entries = [];
        $this->regionOnly = [];

        foreach ($grouped as $key => $rows) {
            $generic = array_values(array_filter(
                $rows,
                fn ($f) => in_array(mb_strtolower((string) ($f->region ?? '')), ['', 'default', 'global', 'placeholder'], true)
            ));

            $chosen = $generic[0] ?? (count($rows) === 1 ? $rows[0] : null);

            if ($chosen === null) {
                $this->regionOnly[] = [
                    'source' => $rows[0]->emissionSource->name,
                    'unit' => (string) $rows[0]->unit,
                    'regions' => implode(', ', array_map(fn ($f) => (string) $f->region, $rows)),
                ];

                continue;
            }

            $entries[$key] = [
                'scope' => $chosen->emissionSource->scope ?? null,
                'source' => $chosen->emissionSource->name,
                'unit' => (string) $chosen->unit,
                'value' => (float) $chosen->factor_value,
                'variants' => count($rows),
                'reference' => trim(
                    ($chosen->dataset_name ?: '').' '
                    .($chosen->organization->code ?? '').' '
                    .($chosen->region ? '('.$chosen->region.')' : '')
                ) ?: null,
            ];
        }

        return $entries;
    }

    /** Source+unit pairs that exist only as region-specific rows. */
    private array $regionOnly = [];

    /** Match key: normalised source name plus normalised unit. */
    private function key(string $source, string $unit): string
    {
        $unit = mb_strtolower(trim($unit));
        $unit = self::UNIT_ALIASES[$unit] ?? $unit;

        return preg_replace('/\s+/', ' ', mb_strtolower(trim($source))).'|'.$unit;
    }

    private function out(string $text = ''): void
    {
        $this->lines[] = $text;
        $this->getOutput()->writeln($text);
    }

    private function render(
        array $config,
        array $database,
        array $conflicts,
        array $agreements,
        array $onlyInConfig,
        array $onlyInDatabase,
        float $tolerance,
    ): void {
        $this->out('# Emission factor reconciliation');
        $this->out();
        $this->out(sprintf('Built-in catalogue entries (priceable): **%d**', count($config)));
        $this->out(sprintf('Database factor rows:                  **%d**', count($database)));
        $this->out(sprintf('Matched on source + unit:              **%d**', count($conflicts) + count($agreements)));
        $this->out(sprintf('  agree within %.1f%%:                   **%d**', $tolerance * 100, count($agreements)));
        $this->out(sprintf('  DISAGREE:                            **%d**', count($conflicts)));
        $this->out(sprintf('Only in the built-in catalogue:        **%d**', count($onlyInConfig)));
        $this->out(sprintf('Only in the database:                  **%d**', count($onlyInDatabase)));
        $this->out(sprintf('Scope 2 grid sources (region-keyed, not comparable): **%d**', $this->gridSourceCount));
        $this->out(sprintf('Database pairs with only region-specific rows (no generic baseline): **%d**', count($this->regionOnly)));
        $this->out();
        $this->out('> Comparison is against each source\'s GENERIC row (default / Global / no region).');
        $this->out('> Country-specific rows are supposed to differ and are not treated as conflicts.');
        $this->out();

        $this->out('## Conflicts — each needs an accounting decision');
        $this->out();

        if ($conflicts === []) {
            $this->out('_None._');
        } else {
            $this->out('| Source | Unit | Database | Catalogue | Difference | DB provenance | Catalogue provenance |');
            $this->out('|---|---|---:|---:|---:|---|---|');
            foreach ($conflicts as $c) {
                $this->out(sprintf(
                    '| %s | %s | %s | %s | %+.1f%% | %s | %s |',
                    $c['source'],
                    $c['unit'],
                    rtrim(rtrim(number_format($c['db_value'], 10, '.', ''), '0'), '.') ?: '0',
                    rtrim(rtrim(number_format($c['value'], 10, '.', ''), '0'), '.') ?: '0',
                    $c['value'] > 0 ? ($c['db_value'] - $c['value']) / $c['value'] * 100 : 100,
                    $c['db_reference'] ?: '—',
                    $c['reference'] ?: '—'
                ));
            }
        }

        $this->out();
        $this->out('## Present only in the built-in catalogue');
        $this->out();
        $this->out(sprintf('_%d entries. These become new rows when the catalogue is compiled in._', count($onlyInConfig)));

        if ($onlyInConfig !== []) {
            $this->out();
            $this->out('| Scope | Source | Unit | Factor |');
            $this->out('|---|---|---|---:|');
            foreach (array_slice($onlyInConfig, 0, 40) as $e) {
                $this->out(sprintf(
                    '| %d | %s | %s | %s |',
                    $e['scope'],
                    $e['source'],
                    $e['unit'],
                    rtrim(rtrim(number_format($e['value'], 10, '.', ''), '0'), '.') ?: '0'
                ));
            }
            if (count($onlyInConfig) > 40) {
                $this->out(sprintf('| … | _%d more not listed_ | | |', count($onlyInConfig) - 40));
            }
        }

        $this->out();
        $this->out('## Present only in the database');
        $this->out();
        $this->out(sprintf(
            '_%d rows. Each is either a factor the catalogue lacks, or the same factor under a different name or unit spelling._',
            count($onlyInDatabase)
        ));

        if ($this->option('all') && $agreements !== []) {
            $this->out();
            $this->out('## Agreements');
            $this->out();
            $this->out('| Source | Unit | Value |');
            $this->out('|---|---|---:|');
            foreach ($agreements as $a) {
                $this->out(sprintf('| %s | %s | %s |', $a['source'], $a['unit'],
                    rtrim(rtrim(number_format($a['value'], 10, '.', ''), '0'), '.') ?: '0'));
            }
        }

        $this->out();
        $this->out('---');
        $this->out();
        $this->out('Read only — nothing was changed. Resolving a conflict restates every historical');
        $this->out('record priced at the losing value, so each decision needs recording alongside');
        $this->out('the figure it changes.');
    }
}
