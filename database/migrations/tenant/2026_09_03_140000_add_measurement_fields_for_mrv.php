<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sheets 3e1 and 3e2 — the measurement-based approach.
 *
 * A facility can determine emissions by CONTINUOUSLY MEASURING the flue gas
 * rather than by calculating from activity data: CO2 concentration multiplied
 * by stack flow, integrated over the year. That is how large combustion plant,
 * cement kilns and steel works are actually monitored, and none of it fits the
 * calculation-based columns the MRV layer had.
 *
 * WHAT GOES WHERE
 *
 * mrv_emission_sources gains the accuracy columns 3e1(b) asks for. They mirror
 * what mrv_source_streams already carries, and deliberately are NOT shared with
 * it: under a measurement approach the tier and the uncertainty are properties
 * of the emission SOURCE — the stack being measured — not of a fuel stream
 * entering the process. The same facility can hold both, priced both ways.
 *
 * mrv_measuring_instruments gains the descriptive half of 3e2(b). The table was
 * modelled on the EU-ETS instrument specification (ranges, specified
 * uncertainty) and the workbook asks for something wider: the same physical
 * measurement point, plus which emission source it serves and the procedures
 * governing it. One entity, more fields, rather than a second table describing
 * the same steel pipe.
 *
 * mrv_facility_reports gains the two narratives 3e2 requires and the comment
 * box. `measurement_derivation` is the one an assurer reads hardest: it has to
 * say how annual emissions were derived from concentration and flow, at what
 * frequency, AND what was substituted when the analyser was offline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mrv_emission_sources', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier_level')->nullable()->after('materiality');
            $table->decimal('uncertainty_pct', 8, 4)->nullable()->after('tier_level');

            // 3e1(b) column F. "CO2 emission sources", "N2O emission sources" —
            // the MRR's own categories, not a fuel type.
            $table->string('emission_stream_type', 255)->nullable()->after('uncertainty_pct');
            $table->string('accuracy_source', 255)->nullable()->after('emission_stream_type');
        });

        Schema::table('mrv_measuring_instruments', function (Blueprint $table) {
            // 3e2(b) column C. The existing source_stream_code points at a fuel
            // stream; a measurement point serves an emission source.
            $table->string('emission_source_code', 20)->nullable()->after('source_stream_code');

            $table->text('procedures')->nullable()->after('emission_source_code');
            $table->string('relevant_procedures', 255)->nullable()->after('procedures');
            $table->string('relevant_source', 255)->nullable()->after('relevant_procedures');
        });

        Schema::table('mrv_facility_reports', function (Blueprint $table) {
            $table->text('measurement_approach')->nullable()->after('fallback_justification');
            $table->text('measurement_derivation')->nullable()->after('measurement_approach');
            $table->text('measurement_comments')->nullable()->after('measurement_derivation');
        });
    }

    public function down(): void
    {
        Schema::table('mrv_emission_sources', function (Blueprint $table) {
            $table->dropColumn(['tier_level', 'uncertainty_pct', 'emission_stream_type', 'accuracy_source']);
        });

        Schema::table('mrv_measuring_instruments', function (Blueprint $table) {
            $table->dropColumn(['emission_source_code', 'procedures', 'relevant_procedures', 'relevant_source']);
        });

        Schema::table('mrv_facility_reports', function (Blueprint $table) {
            $table->dropColumn(['measurement_approach', 'measurement_derivation', 'measurement_comments']);
        });
    }
};
