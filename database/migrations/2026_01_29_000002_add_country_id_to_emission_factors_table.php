<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            if (!Schema::hasColumn('emission_factors', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('organization_id');
                $table->index(['emission_source_id', 'organization_id', 'country_id'], 'emission_factors_source_org_country_idx');
            }
        });

        if (Schema::hasTable('emission_factors') && Schema::hasTable('countries')) {
            Schema::table('emission_factors', function (Blueprint $table) {
                try {
                    $table->foreign('country_id')
                        ->references('id')
                        ->on('countries')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // no-op
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('emission_factors')) {
            Schema::table('emission_factors', function (Blueprint $table) {
                if (Schema::hasColumn('emission_factors', 'country_id')) {
                    try { $table->dropForeign(['country_id']); } catch (\Throwable $e) {}
                    try { $table->dropIndex('emission_factors_source_org_country_idx'); } catch (\Throwable $e) {}
                    $table->dropColumn('country_id');
                }
            });
        }
    }
};

