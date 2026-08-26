<?php

namespace App\Console\Commands;

use App\Models\EmissionFactor;
use App\Models\EmissionRecord;
use Illuminate\Console\Command;

/**
 * Resolves the factor conflicts that `factors:reconcile` reports.
 *
 * Every decision here follows a stated rule rather than a preference, because
 * each one changes what a future inventory reports:
 *
 *   - the platform's declared GWP basis (config/gwp.php factor_basis) wins over
 *     a row using a different assessment report;
 *   - IPCC 2006 inventory treatment wins on what counts as an emission;
 *   - GHG Protocol wins on what belongs in the Scope 1 total at all;
 *   - where a label is ambiguous, the gas that matches the source's actual use.
 *
 * In every case the resolution happens to be "adopt the built-in catalogue
 * value", which is consistent with the catalogue being the better-documented
 * library — the database rows carry no dataset_name or dataset_version at all.
 *
 * Only the GENERIC row (default / Global / placeholder / no region) is touched.
 * Country-specific rows are supposed to differ and are left alone.
 */
class AlignFactorConflicts extends Command
{
    protected $signature = 'factors:align
                            {--apply : Write the changes. Without this the command only reports.}';

    protected $description = 'Resolve the emission-factor conflicts between the two libraries';

    /**
     * @var array<int, array{source: string, unit: string, value: float, rule: string, rationale: string}>
     */
    private const DECISIONS = [
        [
            'source' => 'Biomass Combustion',
            'unit' => 'kg',
            'value' => 0.00002964,
            'rule' => 'IPCC 2006 inventory treatment',
            'rationale' => 'Biogenic CO2 is excluded from the total, but the CH4 and N2O released by '
                .'burning biomass are inventory emissions and are counted (IPCC Table 2.4/2.5). '
                .'A flat zero under-reports every biomass record.',
        ],
        [
            'source' => 'Fire Suppression (Halon)',
            'unit' => 'kg',
            'value' => 0.0,
            // A GWP is a physical constant; it does not vary by country, so every
            // row for this gas is aligned. The `region` column on these rows holds
            // dataset annotations ("Estimated", "AR6 (HFC-134a)", "AR6 GWP-100")
            // rather than geographies, so the generic/region-specific distinction
            // does not apply to them.
            'all_regions' => true,
            'rule' => 'GHG Protocol Corporate Standard, scope of the Kyoto basket',
            'rationale' => 'Halons are Montreal Protocol substances. They are excluded from the Scope 1 '
                .'total and disclosed separately, so zero is correct here — but it must be a stated '
                .'exclusion, not a silent zero. See the catalogue note shown on the entry form.',
        ],
        [
            'source' => 'Fire Suppression (HFCs)',
            'unit' => 'kg',
            'value' => 3.22,
            'all_regions' => true,
            'rule' => 'Gas identity must match the source it describes',
            'rationale' => 'The database row held 1.43, which is HFC-134a (GWP 1430) — a refrigerant, not '
                .'a fire suppressant. Clean-agent fire suppression uses HFC-227ea (FM-200), GWP 3220 '
                .'under AR5. "HFCs" is a family, not a gas; the value must match the stated gas.',
        ],
        [
            'source' => 'C2F6 (PFC-116)',
            'unit' => 'kg',
            'value' => 12.2,
            'all_regions' => true,
            'rule' => 'The platform\'s declared GWP basis',
            'rationale' => 'config/gwp.php sets factor_basis to AR5 and every record is stamped ar5. The '
                .'database row used the AR6 GWP (12400), so a record would state a basis it was not '
                .'computed under. Re-base the whole library to AR6 together, or not at all.',
        ],
    ];

    /** Region values that mean "generic", i.e. not country-specific. */
    private const GENERIC_REGIONS = ['', 'default', 'global', 'placeholder'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $rows = [];
        $blocked = [];

        foreach (self::DECISIONS as $decision) {
            // Changing a factor that historical records were priced at is a
            // restatement, not a correction. Refuse rather than silently do it.
            $usedBy = EmissionRecord::withoutGlobalScopes()
                ->where('emission_source', $decision['source'])
                ->count();

            $factors = EmissionFactor::withoutGlobalScopes()
                ->whereHas('emissionSource', fn ($q) => $q->where('name', $decision['source']))
                ->where('unit', $decision['unit'])
                ->get()
                ->filter(fn ($f) => ($decision['all_regions'] ?? false)
                    || in_array(mb_strtolower((string) ($f->region ?? '')), self::GENERIC_REGIONS, true));

            if ($usedBy > 0) {
                $blocked[] = $decision['source'].' ('.$usedBy.' record(s) priced at the current value)';

                continue;
            }

            foreach ($factors as $factor) {
                $rows[] = [
                    'factor' => $factor,
                    'decision' => $decision,
                    'from' => (float) $factor->factor_value,
                ];
            }
        }

        if ($blocked !== []) {
            $this->error('Refusing to change factors that existing records were priced at:');
            foreach ($blocked as $b) {
                $this->line('  - '.$b);
            }
            $this->line('');
            $this->line('These need a restatement decision, not a factor edit.');

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->info('No conflicting factor rows found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->line('');
        foreach ($rows as $row) {
            $d = $row['decision'];
            $this->line(sprintf(
                '  #%-4d %-28s %-5s  %s  ->  %s',
                $row['factor']->id,
                $d['source'],
                $d['unit'],
                rtrim(rtrim(number_format($row['from'], 10, '.', ''), '0'), '.') ?: '0',
                rtrim(rtrim(number_format($d['value'], 10, '.', ''), '0'), '.') ?: '0'
            ));
            $this->line('        rule: '.$d['rule']);
        }

        $this->line('');
        $this->info(count($rows).' factor row(s) would change. No emission record is priced at any of them.');

        if (! $apply) {
            $this->line('');
            $this->comment('Dry run — nothing written. Re-run with --apply to commit these changes.');

            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            $factor = $row['factor'];
            $d = $row['decision'];

            // Per-model save so the Auditable trait records who changed the
            // factor and from what — the point of auditing the factor library.
            $factor->factor_value = $d['value'];
            $factor->dataset_name = $factor->dataset_name ?: 'Reconciled: '.$d['rule'];
            $factor->source_reference = $d['rationale'];
            $factor->gwp_version = 'ar5';
            $factor->save();
        }

        $this->line('');
        $this->info('Applied. Each change is recorded in the audit trail with its rationale.');

        return self::SUCCESS;
    }
}
