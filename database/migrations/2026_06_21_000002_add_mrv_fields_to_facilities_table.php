<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MRV layer — promote facilities to first-class regulated entities.
 *
 * EAD/EU-ETS submissions are anchored at facility level and require regulatory
 * identifiers (economic licence, environmental permit, coordinates, sector). All
 * fields are nullable and `mrv_enabled` defaults to false, so existing facilities
 * and all non-regulated companies are unaffected — MRV is strictly opt-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('mrv_enabled')->default(false)->after('country');     // per-facility opt-in toggle
            $table->string('economic_licence_number')->nullable()->after('mrv_enabled');
            $table->string('environmental_permit_no')->nullable()->after('economic_licence_number');
            $table->string('parent_entity')->nullable()->after('environmental_permit_no');
            $table->string('coordinates')->nullable()->after('parent_entity');    // "lat,lng" of main entrance
            $table->string('primary_sector')->nullable()->after('coordinates');   // EAD reference list
            $table->string('primary_activity')->nullable()->after('primary_sector'); // e.g. "Combustion of fuels"
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn([
                'mrv_enabled', 'economic_licence_number', 'environmental_permit_no',
                'parent_entity', 'coordinates', 'primary_sector', 'primary_activity',
            ]);
        });
    }
};
