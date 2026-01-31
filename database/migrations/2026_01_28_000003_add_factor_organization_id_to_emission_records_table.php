<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('emission_records', 'factor_organization_id')) {
            Schema::table('emission_records', function (Blueprint $table) {
                $table->unsignedBigInteger('factor_organization_id')->nullable()->after('emission_factor');
            });
        }

        if (Schema::hasTable('emission_records') && Schema::hasTable('factor_organizations')) {
            Schema::table('emission_records', function (Blueprint $table) {
                try {
                    $table->foreign('factor_organization_id')
                        ->references('id')
                        ->on('factor_organizations')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // FK already exists or driver quirk
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('emission_records') && Schema::hasColumn('emission_records', 'factor_organization_id')) {
            Schema::table('emission_records', function (Blueprint $table) {
                $table->dropForeign(['factor_organization_id']);
            });
            Schema::table('emission_records', function (Blueprint $table) {
                $table->dropColumn('factor_organization_id');
            });
        }
    }
};

