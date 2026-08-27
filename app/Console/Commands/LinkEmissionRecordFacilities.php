<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Models\EmissionRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reports emission records whose facility or department name matches no row,
 * and re-runs the linking for those that do.
 *
 * The backfill migration links what it can. What it cannot link is the point:
 * a name matching nothing is a site recorded under a spelling that no longer
 * exists, or never did — which is exactly the drift GHG-08 is about, and it is
 * invisible until something counts it.
 */
class LinkEmissionRecordFacilities extends Command
{
    use RequiresTenant;

    protected $signature = 'emissions:link-facilities
        {--report : Only report. Without this, unlinked records that now match are linked}';

    protected $description = 'Link emission records to their facility and department, and report the ones that cannot be';

    public function handle(): int
    {
        if (! $this->ensureTenantContext()) {
            return self::FAILURE;
        }

        $unlinked = $this->unlinked('facility');
        $unlinkedDepartments = $this->unlinked('department');

        $this->components->twoColumnDetail(
            'Records with a facility name but no link',
            (string) $unlinked->sum('records')
        );
        $this->components->twoColumnDetail(
            'Records with a department name but no link',
            (string) $unlinkedDepartments->sum('records')
        );

        $this->report('Facility names matching nothing', $unlinked);
        $this->report('Department names matching nothing', $unlinkedDepartments);

        $this->reportAmbiguousNames();

        if ($this->option('report')) {
            return self::SUCCESS;
        }

        // Re-saving lets the model's own resolution run, so a name that has
        // since been created as a facility gets linked without this command
        // repeating the matching rules.
        $relinked = 0;

        EmissionRecord::withoutGlobalScope('company')
            ->where(function ($query) {
                $query->whereNull('facility_id')->orWhereNull('department_id');
            })
            ->chunkById(500, function ($records) use (&$relinked) {
                foreach ($records as $record) {
                    $before = [$record->facility_id, $record->department_id];
                    $record->save();

                    if ([$record->facility_id, $record->department_id] !== $before) {
                        $relinked++;
                    }
                }
            });

        $this->newLine();
        $this->components->info("Linked {$relinked} record(s) that now match.");

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function unlinked(string $column)
    {
        return EmissionRecord::withoutGlobalScope('company')
            ->selectRaw("company_id, {$column} as name, COUNT(*) as records")
            ->whereNull($column.'_id')
            ->whereNotNull($column)
            ->whereRaw("TRIM({$column}) <> ''")
            ->groupBy('company_id', $column)
            ->orderByDesc('records')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    protected function report(string $heading, $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->components->warn($heading.':');

        foreach ($rows->take(25) as $row) {
            $this->line(sprintf('    %-40s company %-4s %d record(s)', $row->name, $row->company_id, $row->records));
        }

        if ($rows->count() > 25) {
            $this->line(sprintf('    … and %d more', $rows->count() - 25));
        }
    }

    /**
     * Two facilities with the same name inside one company make any name-based
     * match a coin toss — including the one the backfill already made, which
     * took the lowest id. Worth naming rather than leaving to be discovered.
     */
    protected function reportAmbiguousNames(): void
    {
        foreach (['facilities', 'departments'] as $table) {
            $duplicates = DB::table($table)
                ->selectRaw('company_id, name, COUNT(*) as total')
                ->groupBy('company_id', 'name')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($duplicates->isEmpty()) {
                continue;
            }

            $this->newLine();
            $this->components->error("Duplicate {$table} names within one company — name matching cannot be trusted for these:");

            foreach ($duplicates as $row) {
                $this->line(sprintf('    %-40s company %-4s %d rows', $row->name, $row->company_id, $row->total));
            }
        }
    }
}
