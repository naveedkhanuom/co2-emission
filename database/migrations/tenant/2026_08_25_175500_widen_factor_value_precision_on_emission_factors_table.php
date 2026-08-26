<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `factor_value` was decimal(10,6), which cannot hold the small end of the
     * factor range. The smallest factor in the built-in Scope 1 catalogue is
     * biogas per kWh at 1.9620e-7 tCO2e/kWh; six decimal places store that as
     * 0.000000 — the factor disappears entirely.
     *
     * This matters now because reconciling the two factor libraries (GHG-04)
     * means compiling the config catalogue into this table. Doing that against a
     * six-decimal column would silently zero out every biogenic and low-carbon
     * factor as it was written.
     *
     * Ten decimal places hold every factor in both catalogues exactly, matching
     * emission_records.emission_factor, which was widened for the same reason.
     * The decomposed component columns (co2_factor, ch4_factor, n2o_factor,
     * net_calorific_value) are already decimal(16,8) and need no change.
     *
     * Widening a decimal never truncates existing values, so this is safe to run.
     */
    public function up(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->decimal('factor_value', 20, 10)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->decimal('factor_value', 10, 6)->default(0)->change();
        });
    }
};
