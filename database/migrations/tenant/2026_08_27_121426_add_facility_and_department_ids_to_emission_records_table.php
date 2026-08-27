<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GHG-08 — gives emission records a real reference to their facility and
 * department, instead of only the name typed at the time.
 *
 * Renaming a facility used to orphan every historical record filed under the
 * old name: nothing linked them, so the rename simply left them behind, with
 * no migration and no warning. Two spellings of one site were two sites.
 *
 * The name columns stay, as a denormalised label kept in step with the row
 * they point at — the reporting and analytics paths still group by them, and
 * changing all of those at once would be a large, silent-failure-shaped
 * change. The id is the identity; the string is a cache of its name.
 *
 * unsignedInteger, not foreignId(): facilities.id and departments.id are
 * increments(), so a bigint column could not carry a foreign key to them.
 *
 * Backfill matches on company and name, case- and whitespace-insensitively.
 * Where a company holds two facilities with the same name the lowest id wins
 * — `emissions:link-facilities --report` lists those, because a duplicate name
 * inside one company is itself the data problem this finding is about.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            if (! Schema::hasColumn('emission_records', 'facility_id')) {
                $table->unsignedInteger('facility_id')->nullable()->after('facility');
                $table->index(['company_id', 'facility_id']);
            }

            if (! Schema::hasColumn('emission_records', 'department_id')) {
                $table->unsignedInteger('department_id')->nullable()->after('department');
                $table->index(['company_id', 'department_id']);
            }
        });

        $this->backfill();

        // Added after the backfill so pre-existing rows that cannot be matched
        // do not block the constraint. nullOnDelete rather than cascade: losing
        // a facility must never delete the emissions recorded against it.
        Schema::table('emission_records', function (Blueprint $table) {
            if (Schema::hasTable('facilities')) {
                try {
                    $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
                } catch (\Throwable $e) {
                    // Already present, or the engine refused it — the index and
                    // the backfill are the parts that matter.
                }
            }

            if (Schema::hasTable('departments')) {
                try {
                    $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });
    }

    protected function backfill(): void
    {
        if (! Schema::hasTable('facilities') || ! Schema::hasTable('departments')) {
            return;
        }

        DB::statement("
            UPDATE emission_records er
            SET er.facility_id = (
                SELECT f.id
                  FROM facilities f
                 WHERE f.company_id = er.company_id
                   AND LOWER(TRIM(f.name)) = LOWER(TRIM(er.facility))
                 ORDER BY f.id
                 LIMIT 1
            )
            WHERE er.facility_id IS NULL
              AND er.facility IS NOT NULL
              AND TRIM(er.facility) <> ''
        ");

        // Departments belong to a facility, so a department name is only
        // unambiguous within one. Prefer the department under the facility the
        // record just resolved to; fall back to a company-wide name match for
        // records whose facility could not be matched at all.
        DB::statement("
            UPDATE emission_records er
            SET er.department_id = (
                SELECT d.id
                  FROM departments d
                 WHERE d.company_id = er.company_id
                   AND LOWER(TRIM(d.name)) = LOWER(TRIM(er.department))
                 ORDER BY (d.facility_id = er.facility_id) DESC, d.id
                 LIMIT 1
            )
            WHERE er.department_id IS NULL
              AND er.department IS NOT NULL
              AND TRIM(er.department) <> ''
        ");
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            foreach (['facility_id', 'department_id'] as $column) {
                if (! Schema::hasColumn('emission_records', $column)) {
                    continue;
                }

                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable $e) {
                }

                try {
                    $table->dropIndex(['company_id', $column]);
                } catch (\Throwable $e) {
                }

                $table->dropColumn($column);
            }
        });
    }
};
