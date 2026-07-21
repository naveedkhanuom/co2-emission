<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GHG Protocol Scope 2 dual reporting.
 *
 * Scope 2 must be reported under BOTH the location-based method (grid-average
 * factor) and the market-based method (contractual instruments / supplier or
 * residual-mix factors). We keep the existing `co2e_value` as the location-based
 * figure so every current query/analytic keeps working unchanged, and add a
 * parallel `market_based_co2e`. Reports sum location-based as `co2e_value` and
 * market-based as COALESCE(market_based_co2e, co2e_value) for Scope 2 rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            // location_based | market_based — which method the *primary* figure
            // (co2e_value) represents. For Scope 2 this is location_based by
            // default; null for Scope 1/3 (concept does not apply).
            $table->string('scope2_method', 20)->nullable()->after('calculation_method');

            $table->decimal('market_based_factor', 12, 6)->nullable()->after('emission_factor')
                ->comment('kgCO2e per unit used for the market-based figure');
            $table->decimal('market_based_co2e', 18, 4)->nullable()->after('co2e_value')
                ->comment('Scope 2 market-based emissions in tCO2e');

            $table->unsignedBigInteger('energy_attribute_certificate_id')->nullable()->after('market_based_co2e');
            $table->index('energy_attribute_certificate_id');
        });
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->dropIndex(['energy_attribute_certificate_id']);
            $table->dropColumn([
                'scope2_method',
                'market_based_factor',
                'market_based_co2e',
                'energy_attribute_certificate_id',
            ]);
        });
    }
};
