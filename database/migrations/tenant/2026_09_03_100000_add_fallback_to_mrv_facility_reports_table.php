<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sheet 3f of the EAD workbook — the fall-back approach.
 *
 * An operator may monitor a source stream or emission source without using the
 * tier system at all, where the criteria for it are met. That is the "fall-back"
 * approach, and EAD asks for two things in return: a description of the
 * methodology actually used, including its formulae, and a justification for
 * departing from tiers.
 *
 * The justification carries a hard threshold — the operator must be able to
 * demonstrate that overall uncertainty for the installation's annual emissions
 * does not exceed 7.5% — so it is a substantive claim, not boilerplate, and the
 * competent authority may ask for the full workings behind it.
 *
 * Two text columns rather than one: they answer two different questions (3f(a)
 * and 3f(b)) and land in two separate cells of the workbook. Joining them would
 * mean splitting them again on the way out, on a delimiter a client could type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mrv_facility_reports', function (Blueprint $table) {
            $table->text('fallback_description')->nullable()->after('methane');
            $table->text('fallback_justification')->nullable()->after('fallback_description');
        });
    }

    public function down(): void
    {
        Schema::table('mrv_facility_reports', function (Blueprint $table) {
            $table->dropColumn(['fallback_description', 'fallback_justification']);
        });
    }
};
