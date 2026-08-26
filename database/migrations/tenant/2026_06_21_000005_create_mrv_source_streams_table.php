<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MRV layer — source streams ("F01", "F02"…) under the calculation-based approach
 * (EAD template 2c2 (e), 3d1, 3d2).
 *
 * A source stream is a fuel or material input/output monitored to calculate
 * emissions from a source. It carries the decomposed calculation inputs
 * (activity × NCV × EF × oxidation × conversion), the EU-ETS tier + uncertainty,
 * and a snapshot of the library factor used so the figure is reproducible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrv_source_streams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('facility_id');
            $table->unsignedSmallInteger('reporting_year');

            $table->string('stream_code', 20);             // "F01"
            $table->text('description')->nullable();
            $table->string('emission_source_code', 20)->nullable(); // links to mrv_emission_sources.source_code ("S01")
            $table->enum('classification', ['fuel_combusted', 'other_input', 'output'])->default('fuel_combusted');
            $table->string('fuel_type')->nullable();
            $table->decimal('activity_level', 20, 6)->nullable();
            $table->string('activity_unit')->nullable();   // "MWh"
            $table->string('combustion_device')->nullable();
            $table->decimal('device_capacity', 20, 6)->nullable();
            $table->string('device_capacity_unit')->nullable();

            // Tiers & uncertainty (EU-ETS / EAD 3d1).
            $table->enum('materiality', ['major', 'minor', 'de_minimis'])->nullable();
            $table->unsignedTinyInteger('tier_level')->nullable(); // 1..4
            $table->decimal('uncertainty_pct', 8, 4)->nullable();
            $table->string('accuracy_source')->nullable();         // "Lab. Analysis", etc.

            // Decomposed calculation inputs (snapshot — may be copied from the factor).
            $table->decimal('net_calorific_value', 16, 8)->nullable();
            $table->string('ncv_unit')->nullable();
            $table->decimal('emission_factor_value', 16, 8)->nullable(); // EF per energy
            $table->string('ef_unit')->nullable();
            $table->decimal('oxidation_factor', 8, 6)->nullable();
            $table->decimal('conversion_factor', 8, 6)->nullable();
            $table->unsignedBigInteger('emission_factor_id')->nullable(); // library factor used
            $table->string('information_source')->nullable();             // IPCC, National Inventory…

            $table->decimal('estimated_co2e', 16, 4)->nullable();         // computed result (tonnes)

            $table->timestamps();

            $table->unique(['facility_id', 'reporting_year', 'stream_code'], 'mrv_stream_facility_year_code_unique');
            $table->index(['company_id', 'reporting_year']);

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('emission_factor_id')->references('id')->on('emission_factors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrv_source_streams');
    }
};
