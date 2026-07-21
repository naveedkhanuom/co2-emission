<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MRV layer — measuring instruments used to determine activity data or, for the
 * measurement-based approach, continuous emissions (EAD template 3d2 (c), 3e2).
 *
 * Phase 1 captures the instrument register descriptively (type, location, range,
 * specified uncertainty); the measurement-based numeric path is phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrv_measuring_instruments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('facility_id');
            $table->unsignedSmallInteger('reporting_year');

            $table->string('instrument_code', 20);          // "MI01"
            $table->string('source_stream_code', 20)->nullable(); // associated stream/source ("F01")
            $table->string('type')->nullable();             // "Rotary meter", "Weigh bridge", "CEMS"…
            $table->string('location_id')->nullable();      // internal location reference
            $table->string('range_unit')->nullable();       // "Nm³/h", "Kg"
            $table->decimal('range_lower', 20, 6)->nullable();
            $table->decimal('range_upper', 20, 6)->nullable();
            $table->decimal('specified_uncertainty_pct', 8, 4)->nullable();
            $table->decimal('use_range_lower', 20, 6)->nullable();
            $table->decimal('use_range_upper', 20, 6)->nullable();

            $table->timestamps();

            $table->index(['facility_id', 'reporting_year']);
            $table->index(['company_id', 'reporting_year']);

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrv_measuring_instruments');
    }
};
