<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emission-factor versioning, provenance and gas-level breakdown.
 *
 * Auditors (ISO 14064-3) and disclosure frameworks require knowing exactly which
 * dataset/version produced a number and which GWP set was applied. We keep the
 * aggregate `factor_value` (CO2e per unit) and add:
 *  - dataset provenance (name + version + validity window + source reference)
 *  - the GWP set the aggregate was computed under (ar4/ar5/ar6)
 *  - optional per-gas factors (kg of each gas per activity unit) so reports can
 *    disaggregate CO2 / CH4 / N2O and recompute under a different GWP if needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->string('dataset_name')->nullable()->after('factor_value');     // e.g. DEFRA, EPA, IEA, IPCC
            $table->string('dataset_version', 50)->nullable()->after('dataset_name'); // e.g. 2024
            $table->date('valid_from')->nullable()->after('dataset_version');
            $table->date('valid_to')->nullable()->after('valid_from');
            $table->boolean('is_active')->default(true)->after('valid_to');
            $table->string('gwp_version', 10)->nullable()->after('is_active');     // ar4 | ar5 | ar6
            $table->string('source_reference')->nullable()->after('gwp_version');

            // Optional per-gas factors in kg of gas per activity unit.
            $table->decimal('co2_factor', 16, 8)->nullable()->after('source_reference');
            $table->decimal('ch4_factor', 16, 8)->nullable()->after('co2_factor');
            $table->decimal('n2o_factor', 16, 8)->nullable()->after('ch4_factor');
            $table->decimal('biogenic_co2_factor', 16, 8)->nullable()->after('n2o_factor');

            $table->index(['emission_source_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->dropIndex(['emission_source_id', 'is_active']);
            $table->dropColumn([
                'dataset_name', 'dataset_version', 'valid_from', 'valid_to',
                'is_active', 'gwp_version', 'source_reference',
                'co2_factor', 'ch4_factor', 'n2o_factor', 'biogenic_co2_factor',
            ]);
        });
    }
};
