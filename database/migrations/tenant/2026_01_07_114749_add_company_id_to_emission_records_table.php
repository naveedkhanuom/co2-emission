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
        Schema::table('emission_records', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('site_id')->nullable()->after('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');
            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['site_id']);
            $table->dropIndex(['company_id', 'entry_date']);
            $table->dropIndex(['company_id', 'scope']);
            $table->dropColumn(['company_id', 'site_id']);
        });
    }
};
