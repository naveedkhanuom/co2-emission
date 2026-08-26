<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MRV layer — physical emission sources ("S01", "S02"…) for a facility/year,
 * independent of monitoring method (EAD template 2c2 (d), 3e1).
 *
 * An emission source is a separately identifiable part of an installation from
 * which GHGs are emitted. Each carries its determination methodology and a
 * materiality classification (major / minor / de-minimis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrv_emission_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('facility_id');
            $table->unsignedSmallInteger('reporting_year');

            $table->string('source_code', 20);            // "S01"
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('associated_product', 20)->nullable(); // "P01"
            $table->string('ghg_types')->nullable();       // "CO2", "CH4", "Mixed"
            $table->boolean('energy_related')->default(false);
            $table->boolean('process_emissions')->default(false);
            $table->enum('methodology', ['calculation', 'measurement', 'fallback'])->default('calculation');
            $table->enum('materiality', ['major', 'minor', 'de_minimis'])->nullable();
            $table->decimal('total_co2e', 16, 4)->nullable();

            $table->timestamps();

            $table->unique(['facility_id', 'reporting_year', 'source_code'], 'mrv_source_facility_year_code_unique');
            $table->index(['company_id', 'reporting_year']);

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrv_emission_sources');
    }
};
