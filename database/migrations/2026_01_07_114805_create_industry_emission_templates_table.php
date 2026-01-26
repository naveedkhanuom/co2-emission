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
        Schema::create('industry_emission_templates', function (Blueprint $table) {
            $table->id();
            $table->string('industry_type', 50)->index();
            $table->string('name', 255);
            $table->tinyInteger('scope')->comment('1, 2, or 3');
            $table->string('emission_source', 100);
            $table->string('unit', 20);
            $table->decimal('default_factor', 12, 6);
            $table->string('region', 50)->nullable()->comment('Country/region for region-specific factors');
            $table->string('source_reference', 255)->nullable()->comment('Reference to emission factor source');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Display order');
            $table->timestamps();
            
            $table->index(['industry_type', 'scope']);
            $table->index(['industry_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_emission_templates');
    }
};
