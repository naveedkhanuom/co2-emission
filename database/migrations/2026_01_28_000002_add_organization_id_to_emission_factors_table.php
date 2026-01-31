<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            if (!Schema::hasColumn('emission_factors', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('emission_source_id');
                $table->index(['emission_source_id', 'organization_id'], 'emission_factors_source_org_idx');
            }
        });

        // Add FK only if both tables exist (keeps migrations resilient in dev)
        if (Schema::hasTable('emission_factors') && Schema::hasTable('factor_organizations')) {
            Schema::table('emission_factors', function (Blueprint $table) {
                // Avoid duplicate FK creation if rerun
                try {
                    $table->foreign('organization_id')
                        ->references('id')
                        ->on('factor_organizations')
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
                if (Schema::hasColumn('emission_factors', 'organization_id')) {
                    try { $table->dropForeign(['organization_id']); } catch (\Throwable $e) {}
                    try { $table->dropIndex('emission_factors_source_org_idx'); } catch (\Throwable $e) {}
                    $table->dropColumn('organization_id');
                }
            });
        }
    }
};

