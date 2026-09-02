<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Models\EmissionFactor;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Retires the seeded factors that carry no provenance.
 *
 * Every tenant database still holds 271 rows from the original seed with
 * `dataset_name = NULL`: no publisher, no edition, no citation, no valid_from.
 * They are the last factors on the platform that cannot answer "where did this
 * number come from", which is the question the whole factor pipeline exists to
 * answer. ConfigCatalogueCompiler's supersede step deliberately skips them — it
 * only stands down rows from its own two datasets — so compiling the catalogue
 * left them active alongside their attributed replacements.
 *
 * WHY THIS IS NOT A ONE-LINE UPDATE
 *
 * Deactivating all 271 would silently make some activities unpriceable: where a
 * legacy row is the ONLY active factor for a source and unit, retiring it means
 * new records for that activity save with no factor and no verification. That
 * is a more honest state than an uncited number, but it is a coverage decision
 * with a visible consequence for users, not a cleanup.
 *
 * So the two cases are separated and counted:
 *
 *   REPLACED  an attributed active row already covers the same source and unit.
 *             Retiring is pure gain — the same activity keeps a factor, and
 *             gains a citation.
 *
 *   SOLE      the legacy row is the only cover for that source and unit.
 *             Retiring trades a working figure for an unverified one. Left
 *             alone unless --include-sole says otherwise.
 *
 * Region is deliberately NOT part of the match. The legacy rows carry
 * 'default' / 'Global' / 'UK' while the compiled rows mostly carry NULL, and
 * LibraryFactorCatalog prefers a region-less row when the caller names no
 * region — so a compiled NULL-region row does cover a legacy 'default' one in
 * practice. Matching on region would report almost every legacy row as SOLE
 * and retire nothing.
 */
class RetireLegacyFactors extends Command
{
    use RequiresTenant;

    protected $signature = 'factors:retire-legacy
        {--apply : Actually retire them. Without this the command only reports}
        {--include-sole : Also retire rows that are the only cover for their source and unit}';

    protected $description = 'Stand down the seeded emission factors that carry no provenance';

    public function handle(): int
    {
        $tenant = tenant('id');
        $apply = (bool) $this->option('apply');
        $includeSole = (bool) $this->option('include-sole');

        $legacy = EmissionFactor::query()
            ->with('emissionSource')
            ->whereNull('dataset_name')
            ->where('is_active', true)
            ->get();

        if ($legacy->isEmpty()) {
            $this->components->info("[{$tenant}] No unattributed factors left.");

            return self::SUCCESS;
        }

        [$replaced, $sole] = $this->classify($legacy);

        $this->components->info("[{$tenant}] {$legacy->count()} active factors with no provenance.");
        $this->newLine();
        $this->components->twoColumnDetail('Replaced by an attributed factor', (string) $replaced->count());
        $this->components->twoColumnDetail('Sole cover for their activity', (string) $sole->count());

        if ($sole->isNotEmpty()) {
            $this->newLine();
            $this->components->warn(
                'Retiring the sole-cover rows makes these activities unpriceable — new '
                .'records for them would save with no factor and no verification:'
            );
            $this->listSole($sole);
        }

        $toRetire = $includeSole ? $legacy : $replaced;

        if (! $apply) {
            $this->newLine();
            $this->components->warn(sprintf(
                'Nothing was written. --apply would retire %d of %d.',
                $toRetire->count(),
                $legacy->count()
            ));

            return self::SUCCESS;
        }

        if ($toRetire->isEmpty()) {
            $this->newLine();
            $this->components->info('Nothing to retire.');

            return self::SUCCESS;
        }

        // Same convention the compiler uses: stood down and dated, never
        // deleted. A retired factor is still the provenance for every record
        // that was priced with it, and deleting the row would orphan
        // emission_records.emission_factor_id on historical figures.
        $retired = EmissionFactor::whereIn('id', $toRetire->pluck('id'))
            ->update(['is_active' => false, 'valid_to' => now()->toDateString()]);

        $this->newLine();
        $this->components->info("Retired {$retired} factor(s). Rows kept, marked inactive.");

        return self::SUCCESS;
    }

    /**
     * Split the legacy rows into those an attributed factor already covers and
     * those that are the only cover for their source and unit.
     *
     * @param  Collection<int, EmissionFactor>  $legacy
     * @return array{0: Collection<int, EmissionFactor>, 1: Collection<int, EmissionFactor>}
     */
    private function classify(Collection $legacy): array
    {
        // One query for every attributed (source, unit) pair still active,
        // rather than a query per legacy row.
        $covered = EmissionFactor::query()
            ->whereNotNull('dataset_name')
            ->where('is_active', true)
            ->get(['emission_source_id', 'unit'])
            ->map(fn ($f) => $f->emission_source_id.'|'.mb_strtolower(trim((string) $f->unit)))
            ->flip();

        $key = fn (EmissionFactor $f) => $f->emission_source_id.'|'.mb_strtolower(trim((string) $f->unit));

        return [
            $legacy->filter(fn ($f) => $covered->has($key($f)))->values(),
            $legacy->reject(fn ($f) => $covered->has($key($f)))->values(),
        ];
    }

    /**
     * @param  Collection<int, EmissionFactor>  $sole
     */
    private function listSole(Collection $sole): void
    {
        $this->newLine();

        $rows = $sole
            ->sortBy([fn ($f) => $f->emissionSource?->scope, fn ($f) => $f->emissionSource?->name ?? ''])
            ->map(fn ($f) => [
                $f->emissionSource?->scope ?? '-',
                mb_strimwidth((string) ($f->emissionSource?->name ?? '(missing source)'), 0, 52, '…'),
                $f->unit,
                rtrim(rtrim((string) $f->factor_value, '0'), '.'),
                $f->region ?? '-',
            ])
            ->all();

        $this->table(['Scope', 'Source', 'Unit', 'Factor', 'Region'], $rows);
    }
}
