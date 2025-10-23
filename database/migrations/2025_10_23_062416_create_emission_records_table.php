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
        Schema::create('emission_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('emission_source_id')->constrained()->onDelete('cascade');
            $table->foreignId('emission_factor_id')->nullable()->constrained()->onDelete('set null');
            $table->date('record_date');
            $table->decimal('activity_data', 15, 4); // e.g., liters of fuel consumed
            $table->decimal('emission_value', 15, 4)->nullable(); // calculated emission
            $table->string('unit')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emission_records');
    }
};
