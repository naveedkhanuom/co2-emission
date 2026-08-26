<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The Manual Entry and Scope-Based Entry forms both offer a "How was this
     * data obtained?" dropdown with Meter Reading, Utility Invoice and Estimate.
     * EmissionRecordController validates all three. The column did not have
     * them, and sql_mode includes STRICT_TRANS_TABLES — so picking any of those
     * three and saving failed with an integrity violation.
     *
     * Three of the four options in that dropdown were unusable, which is why
     * only manual / import / api appear in the data.
     *
     * Widening the enum rather than removing the options: the options describe
     * genuinely different provenance, and an assurer cares about the difference
     * between a metered reading and an estimate.
     *
     * Follows 2026_06_17_000001_add_supplier_survey_to_emission_records_data_source,
     * which added 'supplier-survey' the same way.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE emission_records MODIFY data_source
             ENUM('manual','import','api','supplier-survey','meter','invoice','estimate')
             NOT NULL DEFAULT 'manual'"
        );
    }

    public function down(): void
    {
        // Rows using a removed value would be rejected by the narrower column,
        // so fold them back into the closest surviving value first.
        DB::table('emission_records')->whereIn('data_source', ['meter', 'invoice'])->update(['data_source' => 'manual']);
        DB::table('emission_records')->where('data_source', 'estimate')->update(['data_source' => 'manual']);

        DB::statement(
            "ALTER TABLE emission_records MODIFY data_source
             ENUM('manual','import','api','supplier-survey')
             NOT NULL DEFAULT 'manual'"
        );
    }
};
