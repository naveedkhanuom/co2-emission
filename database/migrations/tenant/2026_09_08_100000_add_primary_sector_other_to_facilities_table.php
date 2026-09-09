<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sheet 2c2 of the EAD workbook — the follow-up to the primary sector.
 *
 * K10 asks for the facility's primary business sector from a fixed list whose
 * last option is "Other". C11 then asks, in the template's own words, 'If
 * "other", please specify:', and K11 is where that answer goes.
 *
 * There was nowhere to put it. An operator whose sector is not one of the six
 * named ones — and the list is short enough that plenty are not — could select
 * Other and then had no way to say what Other meant, so the workbook's
 * follow-up shipped blank every time.
 *
 * On `facilities` rather than `mrv_facility_reports` because it qualifies the
 * sector, which lives here too: the pair is one answer, and splitting them
 * across two tables would let a year's submission disagree with the facility
 * about what the facility does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('primary_sector_other', 500)->nullable()->after('primary_sector');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('primary_sector_other');
        });
    }
};
