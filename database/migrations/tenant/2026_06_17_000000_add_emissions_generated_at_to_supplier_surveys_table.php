<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stamp set when a completed survey's answers have been converted into
     * Scope 3 EmissionRecords, so the conversion runs at most once per survey.
     */
    public function up(): void
    {
        Schema::table('supplier_surveys', function (Blueprint $table) {
            $table->timestamp('emissions_generated_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_surveys', function (Blueprint $table) {
            $table->dropColumn('emissions_generated_at');
        });
    }
};
