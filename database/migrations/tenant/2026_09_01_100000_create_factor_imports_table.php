<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per run of `factors:import` — which publisher's file, which exact
 * bytes, when, and how many rows it produced.
 *
 * WHY A TABLE RATHER THAN COLUMNS ON EACH FACTOR
 *
 * The provenance question an assurer asks is "where did this number come from",
 * and the honest answer names a file, not a string. Recording the file's SHA-256
 * once per import and pointing factors at it means a row can be traced to the
 * exact bytes that produced it — and that those bytes can be re-verified against
 * the committed copy in database/factors/sources/ years later.
 *
 * Per-row columns would repeat a 64-character hash across thousands of rows and
 * still not record WHEN the import ran or how much it wrote. This also makes
 * "show me everything that came from DEFRA 2026" a single foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factor_imports', function (Blueprint $table) {
            $table->id();

            // Publisher and edition, e.g. "DEFRA/DESNZ" + "2026".
            $table->string('dataset_name');
            $table->string('dataset_version', 50);

            $table->foreignId('organization_id')->nullable()
                ->constrained('factor_organizations')->nullOnDelete();

            // The file as committed, and its hash. Together these are the claim
            // that a factor is the publisher's number and not a transcription.
            $table->string('source_file');
            $table->string('source_file_hash', 64);
            $table->string('source_url', 500)->nullable();

            // The GWP basis the PUBLISHER used, which is not necessarily the one
            // this application currently runs on. EPA 2025 is AR5; if the app
            // re-bases to AR6, these factors are still AR5 and must keep saying so.
            $table->string('gwp_version', 10)->nullable();

            $table->unsignedInteger('factors_written')->default(0);
            $table->unsignedInteger('sources_created')->default(0);
            $table->unsignedInteger('factors_superseded')->default(0);

            $table->timestamp('imported_at')->nullable();
            $table->string('imported_by')->nullable();

            $table->timestamps();

            $table->index(['dataset_name', 'dataset_version']);
        });

        Schema::table('emission_factors', function (Blueprint $table) {
            // Nullable: the 271 seeded rows and any factor a client adds by hand
            // have no import behind them, and should not pretend otherwise.
            $table->foreignId('factor_import_id')->nullable()->after('organization_id')
                ->constrained('factor_imports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('factor_import_id');
        });

        Schema::dropIfExists('factor_imports');
    }
};
