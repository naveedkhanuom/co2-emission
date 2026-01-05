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
        Schema::dropIfExists('emission_factors');
        
        Schema::create('emission_factors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emission_source_id');
            $table->string('unit');
            $table->decimal('factor_value', 10, 6);
            $table->string('region')->nullable();
            $table->timestamps();

            // Foreign key only if emission_sources table exists
            if (Schema::hasTable('emission_sources')) {
                $table->foreign('emission_source_id')->references('id')->on('emission_sources')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emission_factors');
    }
};

