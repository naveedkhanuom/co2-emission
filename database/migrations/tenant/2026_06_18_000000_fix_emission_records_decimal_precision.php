<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original table stored co2e_value as decimal(12,0) and activity_data as
     * decimal(10,0) — zero decimal places. Since co2e_value is in tonnes CO2e,
     * any value under ~0.5 t rounded to 0 and all others lost their fraction,
     * silently corrupting emissions (especially fractional supplier-survey and
     * OCR-derived records). Widen both to 4 decimal places, matching the model's
     * decimal:4 cast.
     */
    public function up(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->decimal('co2e_value', 18, 4)->change();
            $table->decimal('activity_data', 18, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->decimal('co2e_value', 12, 0)->change();
            $table->decimal('activity_data', 10, 0)->nullable()->change();
        });
    }
};
