<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The unit an activity figure was entered in was never stored: the Scope 1/2
     * entry forms let the user pick one (litres vs gallons, kg vs tonnes) to
     * compute co2e_value, then discarded it. That leaves activity_data unreadable
     * after the fact — "1000" of an unknown thing — and unverifiable for an
     * assurer, who cannot re-derive the figure without knowing the denominator.
     *
     * Nullable and additive: rows written before this column existed keep a NULL
     * unit and are displayed as unknown rather than guessed at.
     */
    public function up(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->string('activity_unit', 30)
                ->nullable()
                ->after('activity_data')
                ->comment('Unit the activity_data figure was entered in, e.g. liters, kg, kWh');
        });
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->dropColumn('activity_unit');
        });
    }
};
