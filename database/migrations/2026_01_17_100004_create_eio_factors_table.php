<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safety: avoid failing if table already exists (common in dev DBs)
        if (Schema::hasTable('eio_factors')) {
            return;
        }

        Schema::create('eio_factors', function (Blueprint $table) {
            $table->id();
            $table->string('sector_code')->nullable(); // Industry/sector code
            $table->string('sector_name');
            $table->string('country', 3)->default('USA'); // ISO country code
            $table->string('currency', 3)->default('USD');
            $table->decimal('emission_factor', 15, 6); // kg CO2e per currency unit
            $table->string('factor_unit')->default('kg_CO2e_per_USD'); // Unit description
            $table->string('data_source')->nullable(); // Source of the factor (e.g., 'EPA', 'DEFRA', 'Custom')
            $table->year('year')->nullable(); // Year of the factor
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('sector_code');
            $table->index('country');
            $table->index('is_active');
            $table->unique(['sector_code', 'country', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eio_factors');
    }
};
