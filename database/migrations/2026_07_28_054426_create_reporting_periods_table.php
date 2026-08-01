<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A reporting period is one inventory year for a company. Locking a period
 * freezes its data (no edits/additions/deletes of records in that year) after
 * a report is filed, and one period can be flagged as the base year — the
 * fixed baseline that targets and year-over-year comparisons are measured
 * against. This is the governance/comparability foundation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedSmallInteger('year');
            $table->enum('status', ['open', 'locked'])->default('open');
            $table->boolean('is_base_year')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_periods');
    }
};
