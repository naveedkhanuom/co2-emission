<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MRV layer — decomposed factor components (EU-ETS / EAD calculation approach).
 *
 * The global engine uses one combined `factor_value` (tCO₂e per unit). Regulated
 * MRV submissions instead decompose that into Net Calorific Value × Emission Factor
 * × Oxidation × Conversion. These columns are OPTIONAL: when null (the normal case)
 * nothing changes and `factor_value` stays authoritative. When all are present they
 * must reconcile with `factor_value` (validated in the MRV calculator), so the two
 * representations never silently diverge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->decimal('net_calorific_value', 16, 8)->nullable()->after('biogenic_co2_factor'); // e.g. 42.3
            $table->string('ncv_unit')->nullable()->after('net_calorific_value');                    // e.g. "TJ/Gg"
            $table->decimal('ef_per_energy', 16, 8)->nullable()->after('ncv_unit');                  // e.g. 73.3
            $table->string('ef_per_energy_unit')->nullable()->after('ef_per_energy');                // e.g. "tCO2/TJ"
            $table->decimal('oxidation_factor', 8, 6)->nullable()->after('ef_per_energy_unit');      // default 1 when used
            $table->decimal('conversion_factor', 8, 6)->nullable()->after('oxidation_factor');       // default 1 when used
            $table->string('ipcc_reference')->nullable()->after('conversion_factor');                // provenance for components
        });
    }

    public function down(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->dropColumn([
                'net_calorific_value', 'ncv_unit', 'ef_per_energy', 'ef_per_energy_unit',
                'oxidation_factor', 'conversion_factor', 'ipcc_reference',
            ]);
        });
    }
};
