<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profile fields the Boundary Advisor reasons over. `business_description` is
 * the primary input — free text describing what the company actually does,
 * which distinguishes cases the coarse `industry_type` enum cannot.
 *
 * All nullable: companies that never run the advisor are unaffected.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('business_description')->nullable()->after('industry_type')
                ->comment('Plain-language description of what the company does');
            $table->string('sub_industry', 50)->nullable()->after('business_description');
            $table->string('isic_code', 20)->nullable()->after('sub_industry')
                ->comment('Optional ISIC/NACE classification code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['business_description', 'sub_industry', 'isic_code']);
        });
    }
};
