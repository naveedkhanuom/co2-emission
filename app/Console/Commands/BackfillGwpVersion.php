<?php

namespace App\Console\Commands;

use App\Models\EmissionRecord;
use App\Support\Gwp;
use Illuminate\Console\Command;

/**
 * Stamps the GWP basis onto records that were written without one.
 *
 * Records created before the Excel import was routed through
 * EmissionEnrichmentService carry gwp_version = NULL. A figure that states no
 * GWP set is not disclosable: CSRD/ESRS E1, CDP and the GHG Protocol all require
 * the basis to be declared alongside the number.
 *
 * This records a fact rather than estimating one. Every affected figure was
 * computed from the bundled factor tables, whose basis is config/gwp.php's
 * `factor_basis` — the same value EmissionEnrichmentService stamps on new
 * records today, and the same value every already-stamped record carries.
 *
 * No emissions figure changes. Only the label describing how it was derived.
 * Writes go through Eloquent so the Auditable trait records each one.
 */
class BackfillGwpVersion extends Command
{
    protected $signature = 'emissions:backfill-gwp
                            {--apply : Write the changes. Without this the command only reports.}';

    protected $description = 'Stamp the GWP basis on emission records that were saved without one';

    public function handle(): int
    {
        $basis = Gwp::factorBasis();

        $missing = EmissionRecord::withoutGlobalScopes()
            ->whereNull('gwp_version')
            ->get(['id', 'company_id', 'entry_date', 'emission_source', 'data_source', 'co2e_value']);

        if ($missing->isEmpty()) {
            $this->info('Every emission record already states a GWP basis. Nothing to do.');

            return self::SUCCESS;
        }

        // If the existing records disagree about their basis, a single blanket
        // value is the wrong answer and a human needs to look first.
        $existing = EmissionRecord::withoutGlobalScopes()
            ->whereNotNull('gwp_version')
            ->distinct()
            ->pluck('gwp_version')
            ->all();

        if (count($existing) > 1) {
            $this->error(
                'Records already carry more than one GWP basis ('.implode(', ', $existing).'). '
                .'Backfilling a single value would misstate some of them — resolve by hand.'
            );

            return self::FAILURE;
        }

        $this->line('');
        $this->info(sprintf('%d record(s) carry no GWP basis. They would be stamped "%s".', $missing->count(), $basis));

        if ($existing !== []) {
            $this->line(sprintf('  Every already-stamped record uses "%s".', $existing[0]));
        }

        $this->line('');
        $this->table(
            ['By data source', 'Records'],
            $missing->groupBy(fn ($r) => $r->data_source ?? 'unknown')
                ->map->count()
                ->map(fn ($count, $source) => [$source, $count])
                ->values()
                ->all()
        );

        if (! $this->option('apply')) {
            $this->line('');
            $this->comment('Dry run — nothing written. Re-run with --apply to commit these changes.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($missing as $record) {
            // Per-model save (not a mass update) so Auditable logs each change.
            $record->gwp_version = $basis;
            $record->save();
            $updated++;
        }

        $this->line('');
        $this->info(sprintf('Stamped %d record(s) with GWP basis "%s". No emissions figure was changed.', $updated, $basis));

        return self::SUCCESS;
    }
}
