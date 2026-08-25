<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `industry_type` enum is too coarse for boundary work: a car-rental firm
 * and a container shipping line are both `transportation` but have completely
 * different inventory boundaries. `sub_industry` lets a template target the
 * narrower case while staying null for the generic ones.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('industry_emission_templates', function (Blueprint $table) {
            $table->string('sub_industry', 50)->nullable()->after('industry_type')
                ->comment('Narrower activity within the industry, e.g. car_rental');

            $table->index(['industry_type', 'sub_industry']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industry_emission_templates', function (Blueprint $table) {
            $table->dropIndex(['industry_type', 'sub_industry']);
            $table->dropColumn('sub_industry');
        });
    }
};
