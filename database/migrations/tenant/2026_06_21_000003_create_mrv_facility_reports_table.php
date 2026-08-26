<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MRV layer — submission header + governance/narrative sheets for one facility,
 * one reporting year (EAD "Deliverable C" workbook).
 *
 * The descriptive sheets (contacts, products, methane, verification, management,
 * mitigation) are stored as JSON rather than dozens of columns — this matches how
 * `company_settings` already holds flexible config and keeps phase 1 light while a
 * draft can be saved and re-edited. Numeric source data lives in the dedicated
 * mrv_* tables created alongside this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrv_facility_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('facility_id');
            $table->unsignedSmallInteger('reporting_year');

            $table->enum('status', ['draft', 'finalised', 'submitted'])->default('draft');
            $table->decimal('estimated_annual_co2e', 16, 4)->nullable();
            $table->text('estimation_justification')->nullable();

            // Descriptive / governance sheets (see EAD template 2c1, 2c2, 3g, 4h, 4i, 4j).
            $table->json('contacts')->nullable();          // primary + alternate contact blocks
            $table->json('products')->nullable();          // [{id, category, technology, capacity, actual, ...}]
            $table->boolean('methane_present')->default(false);
            $table->json('methane')->nullable();           // volume, co2e, sources, LDAR programme
            $table->text('verification_text')->nullable();
            $table->json('data_gaps')->nullable();         // [{id, from, until, description, estimated_co2e, source}]
            $table->json('management')->nullable();         // roles, QA + data-validation procedures
            $table->json('mitigation_measures')->nullable(); // one entry per measure (sheet 4j)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'reporting_year'], 'mrv_report_facility_year_unique');
            $table->index(['company_id', 'reporting_year']);

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrv_facility_reports');
    }
};
