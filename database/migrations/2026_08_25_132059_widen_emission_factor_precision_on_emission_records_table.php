<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `emission_factor` was decimal(10,4) while the model casts it decimal:6 —
     * the column cannot hold what the cast promises, so every factor finer than
     * four decimal places was silently rounded on write and then read back with
     * two zeros on the end that look like precision.
     *
     * This is not hypothetical. Diesel is 0.00268 tCO2e/litre; the stored value
     * is 0.002700 — a 0.75% error, above EmissionFigureVerifier's own 0.5%
     * tolerance. The GHG Protocol report prints this column, so activity ×
     * printed factor does not reconcile with the printed CO2e, which is the
     * first arithmetic an assurer does.
     *
     * The smallest factor in the built-in Scope 1 catalogue is biogas per kWh at
     * 1.962e-7 tCO2e/kWh — four decimal places store that as zero outright.
     * Ten decimal places hold every factor in the catalogue exactly.
     *
     * Sibling of 2026_06_18_000000_fix_emission_records_decimal_precision, which
     * widened co2e_value and activity_data for exactly this reason and missed
     * this column.
     *
     * Widening a decimal never truncates existing values, so this migration is
     * safe to run — but it does NOT repair the 37 rows already rounded. Those
     * need a restatement decision, not a schema change.
     */
    public function up(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->decimal('emission_factor', 20, 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->decimal('emission_factor', 10, 4)->nullable()->change();
        });
    }
};
