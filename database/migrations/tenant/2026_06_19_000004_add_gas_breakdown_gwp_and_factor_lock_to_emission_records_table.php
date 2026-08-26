<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-record factor locking, GWP set, and gas-level breakdown.
 *
 * Each record snapshots the exact factor version and GWP set used to compute it,
 * so figures stay frozen and auditable even after the underlying factor library
 * changes (GHG Protocol restatement requirement). Per-gas CO2e (CO2/CH4/N2O) and
 * biogenic CO2 (reported separately, outside the scopes) feed the CSRD/CDP/GRI
 * disclosure exports.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded per-column so the migration completes cleanly even if an earlier
        // interrupted run already added some of these columns.
        Schema::table('emission_records', function (Blueprint $table) {
            if (! Schema::hasColumn('emission_records', 'emission_factor_id')) {
                $table->unsignedBigInteger('emission_factor_id')->nullable()->after('emission_factor');
            }
            if (! Schema::hasColumn('emission_records', 'factor_dataset')) {
                $table->string('factor_dataset')->nullable()->after('emission_factor_id')
                    ->comment('Snapshot of dataset + version, e.g. "DEFRA 2024"');
            }
            if (! Schema::hasColumn('emission_records', 'gwp_version')) {
                $table->string('gwp_version', 10)->nullable()->after('factor_dataset'); // ar4 | ar5 | ar6
            }
            if (! Schema::hasColumn('emission_records', 'co2e_co2')) {
                $table->decimal('co2e_co2', 18, 4)->nullable()->after('gwp_version');
            }
            if (! Schema::hasColumn('emission_records', 'co2e_ch4')) {
                $table->decimal('co2e_ch4', 18, 4)->nullable()->after('co2e_co2');
            }
            if (! Schema::hasColumn('emission_records', 'co2e_n2o')) {
                $table->decimal('co2e_n2o', 18, 4)->nullable()->after('co2e_ch4');
            }
            if (! Schema::hasColumn('emission_records', 'co2e_other')) {
                $table->decimal('co2e_other', 18, 4)->nullable()->after('co2e_n2o');
            }
            if (! Schema::hasColumn('emission_records', 'biogenic_co2')) {
                $table->decimal('biogenic_co2', 18, 4)->nullable()->after('co2e_other');
            }
        });

        // Add the index separately, only if it does not already exist.
        $indexExists = collect(DB::select('SHOW INDEX FROM emission_records'))
            ->contains(fn ($i) => $i->Key_name === 'emission_records_emission_factor_id_index');
        if (! $indexExists) {
            Schema::table('emission_records', function (Blueprint $table) {
                $table->index('emission_factor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->dropIndex(['emission_factor_id']);
            $table->dropColumn([
                'emission_factor_id', 'factor_dataset', 'gwp_version',
                'co2e_co2', 'co2e_ch4', 'co2e_n2o', 'co2e_other', 'biogenic_co2',
            ]);
        });
    }
};
